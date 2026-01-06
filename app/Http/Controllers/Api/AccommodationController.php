<?php

namespace App\Http\Controllers\Api;

use App\Exports\AccommodationsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Requests\UpdateAccommodationRequest;
use App\Imports\AccommodationsImport;
use App\Models\Accommodation;
use App\Models\AccommodationType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Throwable;

class AccommodationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $query = Accommodation::with('accommodationType');

        // Global search
        $query->when($request->query('q'), function ($q, $searchTerm) {
            return $q->where('name', 'like', "%{$searchTerm}%")
                ->orWhere('accommodation_number', 'like', "%{$searchTerm}%");
        });

        $query->when($request->query('name'), function ($q, $name) {
            return $q->where('name', 'like', "%{$name}%");
        });

        $query->when($request->query('accommodation_number'), function ($q, $number) {
            return $q->where('accommodation_number', 'like', "%{$number}%");
        });

        $query->when($request->query('accommodation_type_id'), function ($q, $typeId) {
            return $q->where('accommodation_type_id', $typeId);
        });

        $query->when($request->query('status'), function ($q, $status) {
            return $q->where('status', $status);
        });

        if ($request->has('page')) {
            $accommodations = $query->paginate(10);
        } else {
            $accommodations = $query->get();
        }

        return response()->json($accommodations);
    }

    /**
     * Retrieve all accommodations.
     */
    public function all(): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $accommodations = Accommodation::all();

        return response()->json($accommodations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAccommodationRequest $request): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $validatedData = $request->validated();

        if (! empty($validatedData['new_accommodation_type'])) {
            $accommodationType = AccommodationType::create([
                'name' => $validatedData['new_accommodation_type'],
            ]);
            $validatedData['accommodation_type_id'] = $accommodationType->id;
        }

        if (! empty($validatedData['accommodation_type_id'])) {
            $accommodationType = AccommodationType::findOrFail($validatedData['accommodation_type_id']);
            $validatedData['type'] = $accommodationType->name;
        }

        if (empty($validatedData['type'])) {
            return response()->json(['message' => 'Accommodation type is required. Please select an existing type or create a new one.'], 422);
        }

        $accommodation = Accommodation::create($validatedData);
        $accommodation->load('accommodationType');

        return response()->json($accommodation, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Accommodation $accommodation): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $accommodation->load('accommodationType');

        return response()->json($accommodation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAccommodationRequest $request, Accommodation $accommodation): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $validatedData = $request->validated();

        if (! empty($validatedData['new_accommodation_type'])) {
            $accommodationType = AccommodationType::create([
                'name' => $validatedData['new_accommodation_type'],
            ]);
            $validatedData['accommodation_type_id'] = $accommodationType->id;
        }

        if (! empty($validatedData['accommodation_type_id'])) {
            $accommodationType = AccommodationType::findOrFail($validatedData['accommodation_type_id']);
            $validatedData['type'] = $accommodationType->name;
        }

        if (isset($validatedData['accommodation_type_id']) && empty($validatedData['type'])) {
            return response()->json(['message' => 'Accommodation type could not be determined. Please ensure you select a valid type.'], 422);
        }

        $accommodation->update($validatedData);
        $accommodation->load('accommodationType');

        return response()->json($accommodation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Accommodation $accommodation): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $accommodation->delete();

        return response()->json(null, 204);
    }

    public function export()
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response('You do not have permission to export accommodations.', 403);
        }

        return Excel::download(new AccommodationsExport, 'accommodations.xlsx');
    }

    public function import(Request $request): JsonResponse
    {
        // Check if the user has the required privilege
        if (! auth()->user()->hasPrivilege('manage-accommodations')) {
            return response()->json(['message' => 'You do not have permission to manage accommodations.'], 403);
        }

        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new AccommodationsImport, $request->file('file'));

            return response()->json(['message' => 'Accommodations imported successfully.']);
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
                'message' => 'Erreur de validation lors de l\'importation.',
                'errors' => $errors,
            ], 422);
        } catch (Throwable $e) {
            return response()->json([
                'message' => 'Une erreur inattendue est survenue lors de l\'importation.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
