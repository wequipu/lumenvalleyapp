<?php

use Illuminate\Support\Facades\DB;

// Quick script to fix the existing records in the database
function fix_reservation_types()
{
    // First, let's see what values currently exist in the database
    $results = DB::select('SELECT DISTINCT reservable_type FROM reservations');
    echo "Current reservable_type values in the database:\n";
    foreach ($results as $result) {
        echo '- '.$result->reservable_type."\n";
    }

    // Update any records with 'service_only' to have null values for reservable_id and appropriate handling
    $count = DB::table('reservations')
        ->where('reservable_type', 'service_only')
        ->update([
            'reservable_id' => null,  // Make sure service_only reservations have null id
        ]);

    echo "Updated $count service_only reservations to have null reservable_id\n";
}

// Now let's run the fix
fix_reservation_types();
