<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Accommodation;
use App\Models\ConferenceRoom;
use App\Models\Reservation;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStatistics(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // New stats
        $accommodationsInMaintenance = Accommodation::where('status', 'maintenance')->count();
        $conferenceRoomsInMaintenance = ConferenceRoom::where('status', 'maintenance')->count();

        $totalAccommodationRevenue = Reservation::where('reservable_type', 'accommodation')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->sum('paid_amount');

        $conferenceRoomRevenue = Reservation::where('reservable_type', 'conference_room')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->sum('paid_amount');

        $reservationHistogram = Reservation::where('status', 'checked-out')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->selectRaw('YEAR(checkout_date) as year, MONTH(checkout_date) as month, SUM(total_price) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();

        $revenueByRoom = Reservation::where('reservable_type', 'accommodation')
            ->where('reservations.status', 'checked-out')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkout_date', [$startDate, $endDate]);
            })
            ->join('accommodations', 'reservations.reservable_id', '=', 'accommodations.id')
            ->select('accommodations.name', DB::raw('SUM(reservations.total_price) as revenue'))
            ->groupBy('accommodations.name')
            ->orderByDesc('revenue')
            ->get();

        // Old stats logic
        $available_rooms = Accommodation::where('status', 'available')->count();
        $occupied_rooms = Accommodation::where('status', 'occupied')->count();
        $upcoming_reservations = Reservation::where('status', 'confirmed')->where('checkin_date', '>', Carbon::now())->count();

        $occupancyByMonth = [
            'accommodations' => Reservation::where('reservable_type', 'accommodation')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('checkin_date', [$startDate, $endDate]);
                })
                ->selectRaw('YEAR(checkin_date) as year, MONTH(checkin_date) as month, COUNT(*) as count, SUM(total_price) as revenue')
                ->groupBy('year', 'month')->get(),
            'conference_rooms' => Reservation::where('reservable_type', 'conference_room')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('checkin_date', [$startDate, $endDate]);
                })
                ->selectRaw('YEAR(checkin_date) as year, MONTH(checkin_date) as month, COUNT(*) as count, SUM(total_price) as revenue')
                ->groupBy('year', 'month')->get(),
        ];

        $topRevenueGenerators = [
            'accommodations' => Reservation::where('reservable_type', 'accommodation')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('checkout_date', [$startDate, $endDate]);
                })
                ->with('reservable')
                ->select('reservable_id', DB::raw('SUM(paid_amount) as revenue'))
                ->groupBy('reservable_id')
                ->orderByDesc('revenue')
                ->take(5)
                ->get()->map(fn ($r) => ['name' => $r->reservable?->name ?? 'Hébergement supprimé', 'revenue' => $r->revenue]),
            'conference_rooms' => Reservation::where('reservable_type', 'conference_room')
                ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                    return $q->whereBetween('checkout_date', [$startDate, $endDate]);
                })
                ->with('reservable')
                ->select('reservable_id', DB::raw('SUM(paid_amount) as revenue'))
                ->groupBy('reservable_id')
                ->orderByDesc('revenue')
                ->take(5)
                ->get()->map(fn ($r) => ['name' => $r->reservable?->name ?? 'Salle supprimée', 'revenue' => $r->revenue]),
        ];

        $occupancyVariation = Reservation::when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            return $q->whereBetween('checkin_date', [$startDate, $endDate]);
        })
            ->selectRaw('DATE(checkin_date) as date, COUNT(*) as count')
            ->groupBy('date')->orderBy('date')->get();

        $mostRequestedServices = Service::withCount(['reservations' => fn ($query) => $query->where('status', '!=', 'canceled')->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
            return $q->whereBetween('reservations.created_at', [$startDate, $endDate]);
        })])
            ->orderByDesc('reservations_count')->take(10)->get()->map(fn ($s) => ['name' => $s->name, 'total_quantity' => $s->reservations_count]);

        $canceledReservationsTrend = Reservation::where('status', 'canceled')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('updated_at', [$startDate, $endDate]);
            })
            ->selectRaw('YEAR(updated_at) as year, MONTH(updated_at) as month, COUNT(*) as count')
            ->groupBy('year', 'month')->get();

        // Calcul des créances (montants restants non payés)
        $totalAccommodationDebt = Reservation::where('reservable_type', 'accommodation')
            ->where('status', '!=', 'canceled')
            ->where('remaining_balance', '>', 0)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->sum('remaining_balance');

        $totalConferenceRoomDebt = Reservation::where('reservable_type', 'conference_room')
            ->where('status', '!=', 'canceled')
            ->where('remaining_balance', '>', 0)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->sum('remaining_balance');

        $totalServiceOnlyDebt = Reservation::where('reservable_type', 'service_only')
            ->where('status', '!=', 'canceled')
            ->where('remaining_balance', '>', 0)
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->sum('remaining_balance');

        // Calcul des revenus des services - basé sur le ratio de paiement des services dans chaque réservation
        $allReservations = Reservation::where('status', '!=', 'canceled')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('checkin_date', [$startDate, $endDate]);
            })
            ->with(['services'])
            ->get();

        $serviceRevenue = 0;
        foreach ($allReservations as $reservation) {
            // Calculate the service portion of the paid amount based on the proportion of service costs
            $totalServicesCost = $reservation->services->sum(function ($service) {
                return $service->pivot->quantity * $service->pivot->price;
            });

            if ($reservation->total_price > 0) {
                $serviceRatio = $totalServicesCost / $reservation->total_price;
                $servicePaidAmount = $reservation->paid_amount * $serviceRatio;
                $serviceRevenue += $servicePaidAmount;
            }
        }

        return response()->json([
            'accommodations_in_maintenance' => $accommodationsInMaintenance,
            'conference_rooms_in_maintenance' => $conferenceRoomsInMaintenance,
            'total_accommodation_revenue' => $totalAccommodationRevenue,
            'conference_room_revenue' => $conferenceRoomRevenue,
            'service_revenue' => $serviceRevenue,
            'reservation_histogram' => $reservationHistogram,
            'revenue_by_room' => $revenueByRoom,
            'available_rooms' => $available_rooms,
            'occupied_rooms' => $occupied_rooms,
            'upcoming_reservations' => $upcoming_reservations,
            'occupancy_by_month' => $occupancyByMonth,
            'top_revenue_generators' => $topRevenueGenerators,
            'occupancy_variation' => $occupancyVariation,
            'most_requested_services' => $mostRequestedServices,
            'canceled_reservations_trend' => $canceledReservationsTrend,
            'debt' => [
                'accommodation_debt' => $totalAccommodationDebt,
                'conference_room_debt' => $totalConferenceRoomDebt,
                'service_only_debt' => $totalServiceOnlyDebt,
                'total_debt' => $totalAccommodationDebt + $totalConferenceRoomDebt + $totalServiceOnlyDebt,
            ],
        ]);
    }

    public function getServiceRevenueData(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date')) : null;
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date')) : null;

        // Calcul des revenus par service
        $serviceRevenueData = DB::table('reservation_service')
            ->join('reservations', 'reservation_service.reservation_id', '=', 'reservations.id')
            ->join('services', 'reservation_service.service_id', '=', 'services.id')
            ->where('reservations.status', '!=', 'canceled')
            ->when($startDate && $endDate, function ($q) use ($startDate, $endDate) {
                return $q->whereBetween('reservations.checkin_date', [$startDate, $endDate]);
            })
            ->select(
                'services.name as serviceName',
                DB::raw('SUM(reservation_service.quantity) as quantity'),
                DB::raw('SUM(reservation_service.price * reservation_service.quantity) as revenue')
            )
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(function ($item) {
                return [
                    'serviceName' => $item->serviceName,
                    'quantity' => $item->quantity,
                    'revenue' => $item->revenue,
                ];
            })
            ->toArray();

        return response()->json($serviceRevenueData);
    }
}
