<?php

namespace App\Http\Controllers;

use App\Models\Privilege;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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

        return response()->json($privilege, 201);
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
            'name' => ['required', 'string', Rule::unique('privileges')->ignore($privilege->id)],
            'description' => 'nullable|string',
        ]);

        $privilege->update($request->all());

        return $privilege;
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Privilege $privilege)
    {
        $privilege->delete();

        return response()->noContent();
    }
}
