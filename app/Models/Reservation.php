<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class Reservation extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'client_id',
        'reservable_id',
        'reservable_type',
        'checkin_date',
        'checkout_date',
        'total_price',
        'status',
        'paid_amount',
        'remaining_balance',
        'rate_type',
        'duration_units',
        // Champs pour les remises et taxes
        'accommodation_discount_percent',
        'conference_room_discount_percent',
        'services_discount_percent',
        'accommodation_tax_rate',
        'conference_room_tax_rate',
        'services_tax_rate',
        'accommodation_subtotal_ht',
        'conference_room_subtotal_ht',
        'services_subtotal_ht',
        'accommodation_subtotal_ttc',
        'conference_room_subtotal_ttc',
        'services_subtotal_ttc',
        'total_ttc',
        'uses_tax_system',
    ];

    protected $with = ['client', 'payments'];

    protected $appends = ['number_of_nights', 'duration_display', 'pricing_summary', 'unit_price', 'discount_amount', 'tax_amount'];

    /**
     * Get the client that owns the reservation.
     */
    public function client(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    /**
     * Get the parent reservable model (accommodation or conference room).
     */
    public function reservable(): \Illuminate\Database\Eloquent\Relations\MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the payments for the reservation.
     */
    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The services that belong to the reservation.
     */
    public function services(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'reservation_service')->withPivot('quantity', 'price');
    }

    /**
     * Update the total_price, paid_amount, and remaining_balance of the reservation.
     */
    public function updateBalance()
    {
        // Calculate total price from services
        $servicesPrice = $this->services()->sum(\DB::raw('reservation_service.price * reservation_service.quantity'));

        // Calculate total price from reservable (e.g., accommodation)
        $reservablePrice = 0;
        if ($this->reservable && $this->reservable_type !== 'service_only') {
            if ($this->reservable_type === 'accommodation') {
                $reservablePrice = $this->reservable->nightly_rate * $this->number_of_nights;
            } elseif ($this->reservable_type === 'conference_room') {
                if ($this->rate_type === 'hourly') {
                    $reservablePrice = $this->reservable->hourly_rate * $this->duration_units;
                } elseif ($this->rate_type === 'daily') {
                    $reservablePrice = $this->reservable->daily_rate * $this->duration_units;
                }
            }
        }

        $this->total_price = $reservablePrice + $servicesPrice;

        // Update paid amount
        $paidAmount = $this->payments()->sum('amount');
        $this->paid_amount = $paidAmount;

        // Update remaining balance
        $this->remaining_balance = $this->total_price - $paidAmount;

        $this->save();
    }

    /**
     * Get the number of nights for the reservation.
     */
    public function getNumberOfNightsAttribute(): int
    {
        if ($this->checkin_date && $this->checkout_date) {
            return (new \Carbon\Carbon($this->checkin_date))->diffInDays(new \Carbon\Carbon($this->checkout_date));
        }

        return 0;
    }

    /**
     * Get the formatted duration for display.
     */
    public function getDurationDisplayAttribute(): string
    {
        if ($this->reservable_type === 'accommodation') {
            $nights = $this->number_of_nights;

            return $nights.($nights > 1 ? ' Nuitées' : ' Nuitée');
        }

        if ($this->reservable_type === 'conference_room') {
            if ($this->rate_type === 'hourly') {
                return $this->duration_units.($this->duration_units > 1 ? ' Heures' : ' Heure');
            }
            if ($this->rate_type === 'daily') {
                return $this->duration_units.($this->duration_units > 1 ? ' Jours' : ' Jour');
            }
        }

        return 'N/A';
    }

    /**
     * Calculate pricing summary for the reservation
     */
    public function getPricingSummaryAttribute(): array
    {
        return [
            'accommodation' => [
                'subtotal_ht' => $this->accommodation_subtotal_ht,
                'discount_percent' => $this->accommodation_discount_percent,
                'discount_amount' => $this->accommodation_subtotal_ht * ($this->accommodation_discount_percent / 100),
                'subtotal_after_discount_ht' => $this->accommodation_subtotal_ht - ($this->accommodation_subtotal_ht * ($this->accommodation_discount_percent / 100)),
                'tax_rate' => $this->accommodation_tax_rate,
                'tax_amount' => ($this->accommodation_subtotal_ht - ($this->accommodation_subtotal_ht * ($this->accommodation_discount_percent / 100))) * ($this->accommodation_tax_rate / 100),
                'subtotal_ttc' => $this->accommodation_subtotal_ttc,
            ],
            'conference_room' => [
                'subtotal_ht' => $this->conference_room_subtotal_ht,
                'discount_percent' => $this->conference_room_discount_percent,
                'discount_amount' => $this->conference_room_subtotal_ht * ($this->conference_room_discount_percent / 100),
                'subtotal_after_discount_ht' => $this->conference_room_subtotal_ht - ($this->conference_room_subtotal_ht * ($this->conference_room_discount_percent / 100)),
                'tax_rate' => $this->conference_room_tax_rate,
                'tax_amount' => ($this->conference_room_subtotal_ht - ($this->conference_room_subtotal_ht * ($this->conference_room_discount_percent / 100))) * ($this->conference_room_tax_rate / 100),
                'subtotal_ttc' => $this->conference_room_subtotal_ttc,
            ],
            'services' => [
                'subtotal_ht' => $this->services_subtotal_ht,
                'discount_percent' => $this->services_discount_percent,
                'discount_amount' => $this->services_subtotal_ht * ($this->services_discount_percent / 100),
                'subtotal_after_discount_ht' => $this->services_subtotal_ht - ($this->services_subtotal_ht * ($this->services_discount_percent / 100)),
                'tax_rate' => $this->services_tax_rate,
                'tax_amount' => ($this->services_subtotal_ht - ($this->services_subtotal_ht * ($this->services_discount_percent / 100))) * ($this->services_tax_rate / 100),
                'subtotal_ttc' => $this->services_subtotal_ttc,
            ],
            'total_ttc' => $this->total_ttc,
            'uses_tax_system' => $this->uses_tax_system,
        ];
    }

    /**
     * Update the pricing calculation for the reservation
     */
    public function updatePricing($accommodationSubtotal = 0, $conferenceRoomSubtotal = 0, $servicesSubtotal = 0)
    {
        // Mise à jour des sous-totaux HT
        $this->accommodation_subtotal_ht = $accommodationSubtotal;
        $this->conference_room_subtotal_ht = $conferenceRoomSubtotal;
        $this->services_subtotal_ht = $servicesSubtotal;

        // Calcul des sous-totaux après remise (HT)
        $accommodationAfterDiscount = $accommodationSubtotal * (1 - $this->accommodation_discount_percent / 100);
        $conferenceRoomAfterDiscount = $conferenceRoomSubtotal * (1 - $this->conference_room_discount_percent / 100);
        $servicesAfterDiscount = $servicesSubtotal * (1 - $this->services_discount_percent / 100);

        // Calcul des sous-totaux TTC
        $this->accommodation_subtotal_ttc = $accommodationAfterDiscount * (1 + $this->accommodation_tax_rate / 100);
        $this->conference_room_subtotal_ttc = $conferenceRoomAfterDiscount * (1 + $this->conference_room_tax_rate / 100);
        $this->services_subtotal_ttc = $servicesAfterDiscount * (1 + $this->services_tax_rate / 100);

        // Calcul du total TTC
        $this->total_ttc = $this->accommodation_subtotal_ttc + $this->conference_room_subtotal_ttc + $this->services_subtotal_ttc;

        // Mise à jour du prix total - si le système de taxation est activé, on utilise le total TTC
        if ($this->uses_tax_system) {
            $this->total_price = $this->total_ttc;
        } else {
            $this->total_price = $accommodationSubtotal + $conferenceRoomSubtotal + $servicesSubtotal;
        }

        // Mise à jour des montants payés et solde restant
        $paidAmount = $this->payments()->sum('amount');
        $this->paid_amount = $paidAmount;
        $this->remaining_balance = $this->total_ttc - $paidAmount;

        $this->save();
    }

    /**\n     * Get the unit price for the reservation\n     */
    public function getUnitPriceAttribute(): float
    {
        if ($this->reservable_type === 'accommodation' && $this->reservable) {
            // For accommodation, return nightly rate
            return $this->reservable->nightly_rate;
        } elseif ($this->reservable_type === 'conference_room' && $this->reservable) {
            // For conference room, return hourly or daily rate based on rate_type
            if ($this->rate_type === 'hourly') {
                return $this->reservable->hourly_rate;
            } else {
                return $this->reservable->daily_rate;
            }
        } elseif ($this->reservable_type === 'service_only') {
            // For service only, calculate average service rate if there are services
            $totalServiceQuantity = $this->services->sum('pivot.quantity');
            if ($totalServiceQuantity > 0) {
                $totalServicesCost = $this->services->sum(function ($service) {
                    return $service->pivot->quantity * $service->pivot->price;
                });

                return $totalServicesCost / $totalServiceQuantity;
            }
        }

        return 0;
    }

    /**\n     * Get the total discount amount for the reservation\n     */
    public function getDiscountAmountAttribute(): float
    {
        if ($this->uses_tax_system) {
            // Calculate total discount amount across all components
            $accommodationDiscount = $this->accommodation_subtotal_ht * ($this->accommodation_discount_percent / 100);
            $conferenceRoomDiscount = $this->conference_room_subtotal_ht * ($this->conference_room_discount_percent / 100);
            $servicesDiscount = $this->services_subtotal_ht * ($this->services_discount_percent / 100);

            return $accommodationDiscount + $conferenceRoomDiscount + $servicesDiscount;
        }

        // If tax system is not used, there are no discounts
        return 0;
    }

    /**\n     * Get the total tax amount for the reservation\n     */
    public function getTaxAmountAttribute(): float
    {
        if ($this->uses_tax_system) {
            // Calculate total tax amount across all components
            $accommodationSubtotalAfterDiscount = $this->accommodation_subtotal_ht * (1 - $this->accommodation_discount_percent / 100);
            $conferenceRoomSubtotalAfterDiscount = $this->conference_room_subtotal_ht * (1 - $this->conference_room_discount_percent / 100);
            $servicesSubtotalAfterDiscount = $this->services_subtotal_ht * (1 - $this->services_discount_percent / 100);

            $accommodationTax = $accommodationSubtotalAfterDiscount * ($this->accommodation_tax_rate / 100);
            $conferenceRoomTax = $conferenceRoomSubtotalAfterDiscount * ($this->conference_room_tax_rate / 100);
            $servicesTax = $servicesSubtotalAfterDiscount * ($this->services_tax_rate / 100);

            return $accommodationTax + $conferenceRoomTax + $servicesTax;
        }

        // If tax system is not used, there are no taxes
        return 0;
    }
}
