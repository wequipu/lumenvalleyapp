<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrivilegeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Privilege::all();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:privileges,name',
            'description' => 'nullable|string',
        ]);

        $privilege = Privilege::create($request->all());

        return response()->json($privilege, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Privilege $privilege)
    {
        return $privilege;
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Privilege $privilege)
    {
        $request->validate([
            'name' => 'sometimes|required|string|unique:privileges,name,'.$privilege->id,
            'description' => 'nullable|string',
        ]);

        $privilege->update($request->all());

        return response()->json($privilege);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Privilege $privilege)
    {
        $privilege->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
