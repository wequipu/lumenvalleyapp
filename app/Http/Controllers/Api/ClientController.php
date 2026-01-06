<?php

namespace App\Http\Controllers\Api;

use App\Exports\ClientsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Client::query();

        // Global search
        $query->when($request->query('q'), function ($q, $searchTerm) {
            return $q->where(function ($subQuery) use ($searchTerm) {
                $subQuery->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('phone', 'like', "%{$searchTerm}%");
            });
        });

        // Specific field filters
        $query->when($request->query('first_name'), function ($q, $name) {
            return $q->where('first_name', 'like', "%{$name}%");
        });

        $query->when($request->query('last_name'), function ($q, $name) {
            return $q->where('last_name', 'like', "%{$name}%");
        });

        $query->when($request->query('phone'), function ($q, $phone) {
            return $q->where('phone', 'like', "%{$phone}%");
        });

        $query->when($request->query('start_date'), function ($q, $start_date) {
            return $q->whereDate('date_enregistrement', '>=', $start_date);
        });

        $query->when($request->query('end_date'), function ($q, $end_date) {
            return $q->whereDate('date_enregistrement', '<=', $end_date);
        });

        if ($request->has('page')) {
            $clients = $query->paginate(10);
        } else {
            $clients = $query->get();
        }

        return response()->json($clients);
    }

    public function all(): JsonResponse
    {
        $clients = Client::all();

        return response()->json($clients);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientRequest $request): JsonResponse
    {
        $validatedData = $request->validated();
        if (empty($validatedData['date_enregistrement'])) {
            $validatedData['date_enregistrement'] = now();
        } else {
            $validatedData['date_enregistrement'] = \Carbon\Carbon::parse($validatedData['date_enregistrement'])->format('Y-m-d');
        }
        $client = Client::create($validatedData);

        return response()->json($client, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client): JsonResponse
    {
        return response()->json($client);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client): JsonResponse
    {
        $validatedData = $request->validated();
        if (! empty($validatedData['date_enregistrement'])) {
            $validatedData['date_enregistrement'] = \Carbon\Carbon::parse($validatedData['date_enregistrement'])->format('Y-m-d');
        }
        $client->update($validatedData);

        return response()->json($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client): JsonResponse
    {
        $client->delete();

        return response()->json(null, 204);
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $filters = $request->all();

        return Excel::download(new ClientsExport($filters), 'clients.xlsx');
    }

    public function search(Request $request): JsonResponse
    {
        $searchTerm = $request->query('q');

        if (! $searchTerm) {
            return response()->json([]);
        }

        $clients = Client::where('first_name', 'like', "%{$searchTerm}%")
            ->orWhere('last_name', 'like', "%{$searchTerm}%")
            ->orWhere('phone', 'like', "%{$searchTerm}%")
            ->get();

        return response()->json($clients);
    }
}
