<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu - Réservation #{{ $reservation->id }}</title>
    <style>
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #fff;
            font-size: 12px;
            line-height: 1.4;
        }
        .receipt-container {
            width: 100%;
            margin: 0 auto;
            padding: 0;
            position: relative;
            box-sizing: border-box;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 0 15px 15px 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ccc;
        }
        .logo-container {
            flex: 1;
            text-align: left;
        }
        .logo-container img {
            max-width: 150px;
            max-height: 80px;
            object-fit: contain;
        }
        .header-text {
            flex: 1;
            text-align: right;
            margin-left: 20px;
        }
        .receipt-title {
            text-align: center;
            margin: 20px 15px;
            padding: 10px 0;
            border-bottom: 2px solid #333;
        }
        .receipt-title h1 {
            font-size: 22px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0;
            color: #333;
        }
        .watermark {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.05;
            font-size: 150px;
            font-weight: bold;
            color: #0000FF;
            pointer-events: none;
            z-index: -1;
        }
        .watermark-logo {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) scale(1.5);
            opacity: 0.1;
            pointer-events: none;
            z-index: -1;
        }
        .watermark-logo img {
            max-width: 500px;
        }
        h2 {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            margin: 15px 15px 10px 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #333;
        }
        table {
            width: calc(100% - 30px);
            margin: 0 15px 15px 15px;
            border-collapse: collapse;
            font-size: 11px;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .financial-summary table td:first-child {
            font-weight: bold;
            width: 70%;
        }
        .final-mention {
            margin: 20px 15px;
            font-style: italic;
            text-align: left;
            font-size: 11px;
        }
        .payment-modalities {
            margin: 15px 15px;
        }
        .signature {
            margin: 50px 15px 30px 15px;
            text-align: right;
        }
        .manager-name {
            text-decoration: underline;
            font-weight: bold;
            margin-top: 5px;
        }
        .receipt-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            text-align: center;
        }
        .receipt-footer img {
            max-width: 100%;
            max-height: 50px;
            object-fit: contain;
            display: block;
        }
        .logo-placeholder, .watermark-text, .footer-placeholder {
            background-color: #f0f0f0;
            text-align: center;
            padding: 20px;
            border: 1px dashed #ccc;
            color: #888;
        }
        .watermark-text {
            position: absolute;
            top: 45%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            opacity: 0.05;
            font-size: 150px;
            font-weight: bold;
            color: #0000FF;
            pointer-events: none;
            z-index: -1;
        }
        .receipt-footer {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
        }
        .receipt-footer img {
            width: 100%;
            height: auto;
            display: block;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        @php
            $isInvoice = in_array($reservation->status, ['checked-in', 'checked-out']);
            $docType = $isInvoice ? 'facture' : 'proforma';
            $number = rand(1000, 9999) . '/' . date('m/2025') . '/LV';
            $title = ($isInvoice ? 'Facture n°' : 'Proformat n°') . $number;
        @endphp

        @if($reservation->status === 'checked-out')
            @php
                $watermarkLogoPath = public_path('assets/lumen_valley_lgo.png');
                $watermarkLogoData = file_exists($watermarkLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($watermarkLogoPath)) : '';
            @endphp
            @if($watermarkLogoData)
                <div class="watermark-logo">
                    <img src="{{ $watermarkLogoData }}" alt="Logo">
                </div>
            @else
                <div class="watermark">LUMEN VALLEY</div>
            @endif
        @elseif($isInvoice)
             <div class="watermark">LUMEN VALLEY</div>
        @else
            <div class="watermark">PROFORMAT</div>
        @endif

        @php
            $headerLogoPath = public_path('assets/lumen_valley_lgo.png');
            $headerLogoData = file_exists($headerLogoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($headerLogoPath)) : '';
        @endphp
        <div class="receipt-header">
            <div class="logo-container">
                @if($headerLogoData)
                    <img src="{{ $headerLogoData }}" alt="Logo">
                @else
                    <div class="logo-placeholder">LOGO</div>
                @endif
            </div>
            <div class="header-text">
                <p style="margin: 0; font-weight: bold;">Kara, le {{ date('d/m/Y') }}</p>
            </div>
        </div>

        <div class="receipt-title">
            <h1>{{ $title }}</h1>
        </div>

        <div class="client-info">
            <h2>Informations Client</h2>
            <table>
                <tr>
                    <td width="12%">Nom:</td>
                    <td width="38%">{{ $reservation->client->first_name . ' ' . $reservation->client->last_name }}</td>
                    <td width="12%">Email:</td>
                    <td width="38%">{{ $reservation->client->email }}</td>
                </tr>
                <tr>
                    <td>Téléphone:</td>
                    <td>{{ $reservation->client->phone }}</td>
                    <td>Adresse:</td>
                    <td>{{ $reservation->client->address ?? 'N/A' }}</td>
                </tr>
            </table>
        </div>

        <div class="prestation-details">
            <h2>Détails de la Prestation</h2>
            <table>
                <thead>
                    <tr>
                        <th width="35%">Désignation</th>
                        <th width="8%" class="text-center">Qté</th>
                        <th width="15%" class="text-right">Prix Unitaire (FCFA)</th>
                        <th width="10%" class="text-center">Remise</th>
                        <th width="16%" class="text-right">Montant (FCFA)</th>
                        <th width="16%" class="text-right">Montant Remisé (FCFA)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $items = [];
                        $totalInitial = 0;
                        $totalDiscount = 0;
                        $totalFinal = 0;
                        
                        if ($reservation->reservable_type === 'accommodation' && $reservation->reservable) {
                            $nights = $reservation->number_of_nights;
                            $initialAmount = $nights * $reservation->reservable->nightly_rate;
                            $discountAmount = $initialAmount * ($reservation->accommodation_discount_percent / 100);
                            $finalAmount = $initialAmount - $discountAmount;
                            
                            $items[] = [
                                'designation' => 'Chambre ' . $reservation->reservable->name . ' (' . $reservation->reservable->accommodation_number . ')',
                                'quantity' => $nights . ' Nuit(s)',
                                'unit_price' => number_format($reservation->reservable->nightly_rate, 0, ',', ' '),
                                'discount' => $reservation->accommodation_discount_percent . '%',
                                'initial_amount' => number_format($initialAmount, 0, ',', ' '),
                                'discount_amount' => number_format($discountAmount, 0, ',', ' '),
                                'final_amount' => number_format($finalAmount, 0, ',', ' '),
                            ];
                            
                            $totalInitial += $initialAmount;
                            $totalDiscount += $discountAmount;
                            $totalFinal += $finalAmount;
                        } 
                        elseif ($reservation->reservable_type === 'conference_room' && $reservation->reservable) {
                            $rate = $reservation->rate_type === 'hourly' ? $reservation->reservable->hourly_rate : $reservation->reservable->daily_rate;
                            $initialAmount = $reservation->duration_units * $rate;
                            $discountAmount = $initialAmount * ($reservation->conference_room_discount_percent / 100);
                            $finalAmount = $initialAmount - $discountAmount;
                            
                            $items[] = [
                                'designation' => 'Salle ' . $reservation->reservable->name . ' (' . $reservation->reservable->room_number . ')',
                                'quantity' => $reservation->duration_display,
                                'unit_price' => number_format($rate, 0, ',', ' '),
                                'discount' => $reservation->conference_room_discount_percent . '%',
                                'initial_amount' => number_format($initialAmount, 0, ',', ' '),
                                'discount_amount' => number_format($discountAmount, 0, ',', ' '),
                                'final_amount' => number_format($finalAmount, 0, ',', ' '),
                            ];
                            
                            $totalInitial += $initialAmount;
                            $totalDiscount += $discountAmount;
                            $totalFinal += $finalAmount;
                        }
                        
                        // Add services
                        foreach($reservation->services as $service) {
                            $initialAmount = $service->pivot->quantity * $service->pivot->price;
                            $discountPercent = $reservation->services_discount_percent ?? 0;
                            $discountAmount = $initialAmount * ($discountPercent / 100);
                            $finalAmount = $initialAmount - $discountAmount;
                            
                            $items[] = [
                                'designation' => $service->name,
                                'quantity' => $service->pivot->quantity,
                                'unit_price' => number_format($service->pivot->price, 0, ',', ' '),
                                'discount' => $discountPercent . '%',
                                'initial_amount' => number_format($initialAmount, 0, ',', ' '),
                                'discount_amount' => number_format($discountAmount, 0, ',', ' '),
                                'final_amount' => number_format($finalAmount, 0, ',', ' '),
                            ];
                            
                            $totalInitial += $initialAmount;
                            $totalDiscount += $discountAmount;
                            $totalFinal += $finalAmount;
                        }
                        
                        // Add other charges if any
                        if ($reservation->other_charges > 0) {
                            $initialAmount = $reservation->other_charges;
                            $discountAmount = $initialAmount * ($reservation->other_charges_discount_percent ?? 0 / 100);
                            $finalAmount = $initialAmount - $discountAmount;
                            
                            $items[] = [
                                'designation' => 'Autres charges',
                                'quantity' => 1,
                                'unit_price' => number_format($reservation->other_charges, 0, ',', ' '),
                                'discount' => ($reservation->other_charges_discount_percent ?? 0) . '%',
                                'initial_amount' => number_format($initialAmount, 0, ',', ' '),
                                'discount_amount' => number_format($discountAmount, 0, ',', ' '),
                                'final_amount' => number_format($finalAmount, 0, ',', ' '),
                            ];
                            
                                                    $totalInitial += $initialAmount;
                                                    $totalDiscount += $discountAmount;
                                                    $totalFinal += $finalAmount;
                                                }
                            
                                                $taxRate = 0.18; // TVA
                                                $taxAmount = $totalFinal * $taxRate;
                                                $grandTotal = $totalFinal + $taxAmount;
                                            @endphp                    @foreach($items as $item)
                        <tr>
                            <td>{{ $item['designation'] }}</td>
                            <td class="text-center">{{ $item['quantity'] }}</td>
                            <td class="text-right">{{ $item['unit_price'] }} FCFA</td>
                            <td class="text-center">{{ $item['discount'] }}</td>
                            <td class="text-right">{{ $item['initial_amount'] }} FCFA</td>
                            <td class="text-right">{{ $item['final_amount'] }} FCFA</td>
                        </tr>
                                        @endforeach
                                        <tr style="font-weight: bold;">
                                            <td colspan="5" class="text-right">Sous-total brut:</td>
                                            <td class="text-right">{{ number_format($totalInitial, 0, ', ', ' ') }} FCFA</td>
                                        </tr>
                                        <tr style="font-weight: bold; color: red;">
                                            <td colspan="5" class="text-right">Remise totale:</td>
                                            <td class="text-right">- {{ number_format($totalDiscount, 0, ', ', ' ') }} FCFA</td>
                                        </tr>
                                        <tr style="font-weight: bold;">
                                            <td colspan="5" class="text-right">Sous-total net (HT):</td>
                                            <td class="text-right">{{ number_format($totalFinal, 0, ', ', ' ') }} FCFA</td>
                                        </tr>
                                        <tr style="font-weight: bold;">
                                            <td colspan="5" class="text-right">TVA (18%):</td>
                                            <td class="text-right">{{ number_format($taxAmount, 0, ', ', ' ') }} FCFA</td>
                                        </tr>
                                        <tr style="font-weight: bold; font-size: 1.2em; border-top: 2px solid #000;">
                                            <td colspan="5" class="text-right">TOTAL (TTC):</td>
                                            <td class="text-right">{{ number_format($grandTotal, 0, ', ', ' ') }} FCFA</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                    
                            <div class="final-mention">
                                <p>Arrêté cette {{ $docType }} à la somme de : {{ (new \NumberFormatter('fr', \NumberFormatter::SPELLOUT))->format($grandTotal) }} ({{ number_format($grandTotal, 0, ', ', ' ') }}) Francs CFA.</p>
                                <p style="text-align: center; font-weight: bold; font-size: 14px;">
                                    ({{ number_format($grandTotal, 0, ', ', ' ') }} FCFA)
                                </p>
                            </div>
                    
                            <div class="payment-modalities">
                                <h2>Modalités de paiement</h2>
                                <table style="width: 400px; margin: 10px 15px;">
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">Montant total (TTC):</td>
                                        <td style="border: none; padding: 4px 0; text-align: right; font-weight: bold;">{{ number_format($grandTotal, 0, ', ', ' ') }} FCFA</td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">Montant payé:</td>
                                        <td style="border: none; padding: 4px 0; text-align: right; font-weight: bold; color: green;">{{ number_format($reservation->paid_amount ?? 0, 0, ', ', ' ') }} FCFA</td>
                                    </tr>
                                    <tr>
                                        <td style="border: none; padding: 4px 0;">Reste à payer:</td>
                                        <td style="border: none; padding: 4px 0; text-align: right; font-weight: bold; color: red;">{{ number_format(max(0, $grandTotal - ($reservation->paid_amount ?? 0)), 0, ', ', ' ') }} FCFA</td>
                                    </tr>

                                </table>
                            </div>

        <div style="height: 80px;"></div> <!-- Space for footer -->
        


        <div style="position: absolute; bottom: 80px; right: 15px; width: 300px; text-align: right;">
            <p style="margin: 0; font-weight: bold;">Le Manager</p>
            <br><br><br>
            <p style="margin: 0; text-decoration: underline; font-weight: bold;">Mr. AGLAMEY K. Claude</p>
        </div>
        <div style="height: 120px;"></div> <!-- Additional space for signature and footer -->
    </div>
        @php
            $footerImagePath = public_path('assets/pied.jpg');
            $footerImageData = file_exists($footerImagePath) ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($footerImagePath)) : '';
        @endphp
        @if($footerImageData)
        <div class="receipt-footer">
            <img src="{{ $footerImageData }}" alt="Footer">
        </div>
        @endif
</body>
</html>
