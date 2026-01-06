<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Reservation;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function store(Request $request)
    {
        $reservation = Reservation::findOrFail($request->input('reservation_id'));

        if ($reservation->status === 'checked-out' && ! auth()->user()->hasPrivilege('manage-payment-after-checkout')) {
            return response()->json(['message' => 'You do not have permission to manage payments for a checked-out reservation.'], 403);
        }

        $request->validate([
            'reservation_id' => 'required|exists:reservations,id',
            'amount' => [
                'required',
                'numeric',
                'gt:0',
                function ($attribute, $value, $fail) use ($reservation) {
                    if (($reservation->paid_amount + $value) > $reservation->total_price) {
                        $fail('Le montant du paiement ne peut pas dépasser le solde restant.');
                    }
                },
            ],
            'payment_date' => 'required|date',
        ]);

        $payment = Payment::create($request->all());

        $reservation = Reservation::find($request->reservation_id);
        $reservation->updateBalance();

        return response()->json($payment, 201);
    }

    public function destroy(Payment $payment)
    {
        $reservation = $payment->reservation;

        if ($reservation->status === 'checked-out' && ! auth()->user()->hasPrivilege('manage-payment-after-checkout')) {
            return response()->json(['message' => 'You do not have permission to manage payments for a checked-out reservation.'], 403);
        }

        $payment->delete();
        $reservation->updateBalance();

        return response()->json(null, 204);
    }
}
