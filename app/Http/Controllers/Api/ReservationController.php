<?php

namespace App\Http\Controllers\Api;

use App\Exports\ReservationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Http\Requests\UpdateReservationRequest;
use App\Imports\ReservationsImport;
use App\Models\Reservation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Throwable;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Reservation::with(['client', 'payments', 'services']);

        $query->when($request->query('start_date'), function ($q, $start_date) {
            return $q->whereDate('checkin_date', '>=', $start_date);
        });

        $query->when($request->query('end_date'), function ($q, $end_date) {
            return $q->whereDate('checkout_date', '<=', $end_date);
        });

        $query->when($request->query('status'), function ($q, $status) use ($request) {
            $q->where('status', $status);
            if ($status === 'canceled') {
                $q->when($request->query('canceled_at_start'), function ($q, $start_date) {
                    return $q->whereDate('updated_at', '>=', $start_date);
                });
                $q->when($request->query('canceled_at_end'), function ($q, $end_date) {
                    return $q->whereDate('updated_at', '<=', $end_date);
                });
            }
        });

        $query->when($request->query('reservable_search'), function ($q, $searchTerm) {
            return $q->whereHasMorph(
                'reservable',
                ['App\\Models\\Accommodation', 'App\\Models\\ConferenceRoom'], // Exclure service_only du recherche
                function ($query, $type) use ($searchTerm) {
                    if ($type === 'App\\Models\\Accommodation') {
                        $query->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('accommodation_number', 'like', "%{$searchTerm}%");
                    } elseif ($type === 'App\\Models\\ConferenceRoom') {
                        $query->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('room_number', 'like', "%{$searchTerm}%");
                    }
                }
            );
        });

        $reservations = $query->paginate(10);

        // Charger la relation réservable conditionnellement pour les types appropriés
        $reservations->getCollection()->each(function ($reservation) {
            if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
                $reservation->load('reservable');
            }
        });

        // Charger les services pour tous les réservations pour les calculs de taxe/remise
        $reservations->load('services');

        return response()->json($reservations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['reservable_type'] === 'service_only') {
            $validated['reservable_id'] = null;
        }

        // Check for overlapping reservations (only if not service_only)
        if ($validated['reservable_type'] !== 'service_only' && ! empty($validated['reservable_id'])) {
            $isUnavailable = Reservation::where('reservable_id', $validated['reservable_id'])
                ->where('reservable_type', $validated['reservable_type'])
                ->where(function ($query) use ($validated) {
                    $query->where('checkin_date', '<', $validated['checkout_date'])
                        ->where('checkout_date', '>', $validated['checkin_date']);
                })
                ->where('status', '!=', 'canceled')
                ->exists();

            if ($isUnavailable) {
                return response()->json(['message' => 'La ressource sélectionnée n\'est pas disponible pour les dates indiquées.'], 409);
            }
        }

        // --- Price Calculation with Tax System ---
        $totalPrice = 0;
        $accommodationSubtotal = 0;
        $conferenceRoomSubtotal = 0;
        $servicesSubtotal = 0;

        // Calculate price for accommodation or conference room if applicable
        if ($validated['reservable_type'] !== 'service_only' && ! empty($validated['reservable_id'])) {
            $reservableModel = Relation::getMorphedModel($validated['reservable_type']);
            if ($reservableModel) {
                $reservable = $reservableModel::find($validated['reservable_id']);

                if ($reservable) {
                    if ($validated['reservable_type'] === 'accommodation') {
                        $checkin = new \Carbon\Carbon($validated['checkin_date']);
                        $checkout = new \Carbon\Carbon($validated['checkout_date']);
                        $nights = $checkin->diffInDays($checkout);
                        $accommodationSubtotal = $nights * $reservable->nightly_rate;
                        $totalPrice += $accommodationSubtotal;
                    } elseif ($validated['reservable_type'] === 'conference_room') {
                        $rate = $request->input('rate_type') === 'hourly' ? $reservable->hourly_rate : $reservable->daily_rate;
                        $duration = $request->input('duration_units', 1);
                        $conferenceRoomSubtotal = $duration * $rate;
                        $totalPrice += $conferenceRoomSubtotal;
                    }
                }
            }
        }

        $services_to_sync = [];
        if ($request->has('services')) {
            $services_data = $request->input('services');
            $service_ids = array_column($services_data, 'id');
            $services = \App\Models\Service::find($service_ids);

            foreach ($services_data as $service_data) {
                $service = $services->find($service_data['id']);
                if ($service) {
                    $quantity = $service_data['quantity'];
                    $servicesSubtotal += $service->price * $quantity;
                    $totalPrice += $service->price * $quantity;
                    $services_to_sync[$service->id] = ['price' => $service->price, 'quantity' => $quantity];
                }
            }
        }

        // Initialize tax system fields if not provided
        $validated = array_merge($validated, [
            'accommodation_discount_percent' => $validated['accommodation_discount_percent'] ?? 0,
            'conference_room_discount_percent' => $validated['conference_room_discount_percent'] ?? 0,
            'services_discount_percent' => $validated['services_discount_percent'] ?? 0,
            'accommodation_tax_rate' => $validated['accommodation_tax_rate'] ?? 0,
            'conference_room_tax_rate' => $validated['conference_room_tax_rate'] ?? 0,
            'services_tax_rate' => $validated['services_tax_rate'] ?? 0,
            'uses_tax_system' => $validated['uses_tax_system'] ?? false,
        ]);

        $validated['total_price'] = $totalPrice;
        // --- End Price Calculation ---

        // Format dates for MySQL
        $validated['checkin_date'] = \Carbon\Carbon::parse($validated['checkin_date'])->format('Y-m-d H:i:s');
        $validated['checkout_date'] = \Carbon\Carbon::parse($validated['checkout_date'])->format('Y-m-d H:i:s');

        if ($request->has('rate_type')) {
            $validated['rate_type'] = $request->input('rate_type');
            $validated['duration_units'] = $request->input('duration_units');
        }

        $reservation = Reservation::create($validated);

        if (! empty($services_to_sync)) {
            $reservation->services()->sync($services_to_sync);
        }

        // If tax system is enabled, update pricing with detailed calculations
        if ($reservation->uses_tax_system) {
            $reservation->updatePricing($accommodationSubtotal, $conferenceRoomSubtotal, $servicesSubtotal);
        } else {
            $reservation->updateBalance();
        }

        // Load relations conditionally to avoid issues with service_only reservations
        $reservation->load(['client', 'services', 'payments']);
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
            $reservation->load('reservable');
        }

        return response()->json($reservation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        // Load relations conditionally to avoid issues with service_only reservations
        $reservation->load(['client', 'services']);
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
            $reservation->load('reservable');
        }

        return response()->json($reservation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateReservationRequest $request, Reservation $reservation): JsonResponse
    {
        if ($reservation->status === 'checked-out' && ! auth()->user()->hasPrivilege('edit-reservation-after-checkout')) {
            return response()->json(['message' => 'You do not have permission to edit a reservation after checkout.'], 403);
        }

        $validated = $request->validated();

        if (isset($validated['reservable_type']) && $validated['reservable_type'] === 'service_only') {
            $validated['reservable_id'] = null;
        }

        // --- Price Calculation ---
        $totalPrice = 0;
        $reservableType = $validated['reservable_type'] ?? $reservation->reservable_type;
        $reservableId = isset($validated['reservable_id']) ? $validated['reservable_id'] : $reservation->reservable_id;

        // Calculate price for accommodation or conference room if applicable
        if ($reservableType !== 'service_only' && ! empty($reservableId)) {
            $reservableModel = Relation::getMorphedModel($reservableType);
            if ($reservableModel) {
                $reservable = $reservableModel::find($reservableId);

                if ($reservable) {
                    $checkin = new \Carbon\Carbon($validated['checkin_date'] ?? $reservation->checkin_date);
                    $checkout = new \Carbon\Carbon($validated['checkout_date'] ?? $reservation->checkout_date);

                    if ($reservableType === 'accommodation') {
                        $nights = $checkin->diffInDays($checkout);
                        $totalPrice += $nights * $reservable->nightly_rate;
                    } elseif ($reservableType === 'conference_room') {
                        $rate = $request->input('rate_type') === 'hourly' ? $reservable->hourly_rate : $reservable->daily_rate;
                        $duration = $request->input('duration_units', 1);
                        $totalPrice += $duration * $rate;
                    }
                }
            }
        }

        $services_to_sync = [];
        if ($request->has('services')) {
            $services_data = $request->input('services');
            $service_ids = array_column($services_data, 'id');
            $services = \App\Models\Service::find($service_ids);

            foreach ($services_data as $service_data) {
                $service = $services->find($service_data['id']);
                if ($service) {
                    $quantity = $service_data['quantity'];
                    $totalPrice += $service->price * $quantity;
                    $services_to_sync[$service->id] = ['price' => $service->price, 'quantity' => $quantity];
                }
            }
        } else {
            // If no services are passed, we might want to keep the existing ones
            foreach ($reservation->services as $service) {
                $totalPrice += $service->pivot->price * $service->pivot->quantity;
                $services_to_sync[$service->id] = ['price' => $service->pivot->price, 'quantity' => $service->pivot->quantity];
            }
        }

        $validated['total_price'] = $totalPrice;
        // --- End Price Calculation ---

        // Format dates for MySQL
        if (isset($validated['checkin_date'])) {
            $validated['checkin_date'] = \Carbon\Carbon::parse($validated['checkin_date'])->format('Y-m-d H:i:s');
        }
        if (isset($validated['checkout_date'])) {
            $validated['checkout_date'] = \Carbon\Carbon::parse($validated['checkout_date'])->format('Y-m-d H:i:s');
        }

        if (isset($validated['rate_type'])) {
            $validated['duration_units'] = $request->input('duration_units');
        }

        $validated = array_merge([
            'accommodation_discount_percent' => $reservation->accommodation_discount_percent,
            'conference_room_discount_percent' => $reservation->conference_room_discount_percent,
            'services_discount_percent' => $reservation->services_discount_percent,
            'accommodation_tax_rate' => $reservation->accommodation_tax_rate,
            'conference_room_tax_rate' => $reservation->conference_room_tax_rate,
            'services_tax_rate' => $reservation->services_tax_rate,
            'uses_tax_system' => $reservation->uses_tax_system,
        ], $validated);

        $reservation->update($validated);

        $reservation->services()->sync($services_to_sync);

        // Recalculate pricing with tax system if enabled
        if ($reservation->uses_tax_system) {
            // Recalculate subtotals for accommodation/conference room
            $accommodationSubtotal = 0;
            $conferenceRoomSubtotal = 0;
            $servicesSubtotal = 0;

            if ($reservation->reservable_type === 'accommodation' && $reservation->reservable) {
                $checkin = new \Carbon\Carbon($reservation->checkin_date);
                $checkout = new \Carbon\Carbon($reservation->checkout_date);
                $nights = $checkin->diffInDays($checkout);
                $accommodationSubtotal = $nights * $reservation->reservable->nightly_rate;
            } elseif ($reservation->reservable_type === 'conference_room' && $reservation->reservable) {
                $rate = $reservation->rate_type === 'hourly' ? $reservation->reservable->hourly_rate : $reservation->reservable->daily_rate;
                $duration = $reservation->duration_units ?? 1;
                $conferenceRoomSubtotal = $duration * $rate;
            }

            // Calculate services subtotal
            $servicesSubtotal = $reservation->services->sum(function ($service) {
                return $service->pivot->quantity * $service->pivot->price;
            });

            $reservation->updatePricing($accommodationSubtotal, $conferenceRoomSubtotal, $servicesSubtotal);
        } else {
            $reservation->updateBalance();
        }

        // Load relations conditionally to avoid issues with service_only reservations
        $reservation->load(['client', 'services', 'payments']);
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
            $reservation->load('reservable');
        }

        return response()->json($reservation);
    }

    public function getRevenue(Request $request): JsonResponse
    {
        $query = Reservation::query();

        $query->when($request->query('start_date'), function ($q, $start_date) {
            return $q->whereDate('checkin_date', '>=', $start_date);
        });

        $query->when($request->query('end_date'), function ($q, $end_date) {
            return $q->whereDate('checkout_date', '<=', $end_date);
        });

        $query->when($request->query('status'), function ($q, $status) use ($request) {
            $q->where('status', $status);
            if ($status === 'canceled') {
                $q->when($request->query('canceled_at_start'), function ($q, $start_date) {
                    return $q->whereDate('updated_at', '>=', $start_date);
                });
                $q->when($request->query('canceled_at_end'), function ($q, $end_date) {
                    return $q->whereDate('updated_at', '<=', $end_date);
                });
            }
        });

        $query->when($request->query('reservable_search'), function ($q, $searchTerm) {
            return $q->whereHasMorph(
                'reservable',
                ['App\\Models\\Accommodation', 'App\\Models\\ConferenceRoom'],
                function ($query, $type) use ($searchTerm) {
                    if ($type === 'App\\Models\\Accommodation') {
                        $query->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('accommodation_number', 'like', "%{$searchTerm}%");
                    } elseif ($type === 'App\\Models\\ConferenceRoom') {
                        $query->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('room_number', 'like', "%{$searchTerm}%");
                    }
                }
            );
        });

        $accommodationRevenue = (clone $query)
            ->where('reservable_type', 'accommodation')
            ->sum('paid_amount');

        $conferenceRoomRevenue = (clone $query)
            ->where('reservable_type', 'conference_room')
            ->sum('paid_amount');

        // Calculate services revenue - this is more complex and would require analyzing the specific
        // service amounts within each reservation. For now, we can compute it as the difference
        // between total paid amounts and the main reservation paid amounts.
        // However, a more accurate approach would be to calculate based on service pivot data
        $reservations = (clone $query)->with(['services'])->get();

        $servicesRevenue = 0;
        foreach ($reservations as $reservation) {
            // Calculate the service portion of the paid amount based on the proportion of service costs
            $totalServicesCost = $reservation->services->sum(function ($service) {
                return $service->pivot->quantity * $service->pivot->price;
            });

            if ($reservation->total_price > 0) {
                $serviceRatio = $totalServicesCost / $reservation->total_price;
                $servicePaidAmount = $reservation->paid_amount * $serviceRatio;
                $servicesRevenue += $servicePaidAmount;
            }
        }

        return response()->json([
            'accommodation_revenue' => $accommodationRevenue,
            'conference_room_revenue' => $conferenceRoomRevenue,
            'services_revenue' => $servicesRevenue,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $reservation->delete();

        return response()->json(null, 204);
    }

    public function export(Request $request)
    {
        $filters = $request->all();

        return Excel::download(new ReservationsExport($filters), 'reservations.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new ReservationsImport, $request->file('file'));

            return response()->json(['message' => 'Reservations imported successfully.']);
        } catch (ValidationException $e) {
            $failures = $e->failures();
            $errors = [];

            foreach ($failures as $failure) {
                $errors[] = [
                    'row' => $failure->row(),
                    'attribute' => $failure->attribute(),
                    'errors' => $failure->errors(),
                ];
            }

            return response()->json([
                'message' => 'Validation error during import.',
                'errors' => $errors,
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'An unexpected error occurred during import.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function receipt(Reservation $reservation)
    {
        // Load all necessary relations for the receipt
        $reservation->load(['client', 'services', 'payments']);
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
            $reservation->load('reservable');
        }

        $pdf = Pdf::loadView('receipts.receipt', ['reservation' => $reservation]);

        return $pdf->stream('receipt-'.$reservation->id.'.pdf');
    }

    public function downloadReceiptPdf(Reservation $reservation)
    {
        // Load all necessary relationships
        $reservation->load(['client', 'services', 'payments']);
        if ($reservation->reservable_type !== 'service_only' && ! empty($reservation->reservable_id)) {
            $reservation->load('reservable');
        }

        $pdf = Pdf::loadView('receipts.receipt', ['reservation' => $reservation]);

        // To force download
        return $pdf->download('facture-'.$reservation->id.'.pdf');
    }
}
