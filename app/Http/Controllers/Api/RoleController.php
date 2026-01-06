<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return Role::with('privileges')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'description' => 'nullable|string',
            'privileges' => 'array',
            'privileges.*' => 'exists:privileges,id',
        ]);

        $role = Role::create($request->only('name', 'description'));

        if ($request->has('privileges')) {
            $role->privileges()->attach($request->privileges);
        }

        return response()->json($role->load('privileges'), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        return $role->load('privileges');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'sometimes|required|string|unique:roles,name,'.$role->id,
            'description' => 'nullable|string',
            'privileges' => 'array',
            'privileges.*' => 'exists:privileges,id',
        ]);

        $role->update($request->only('name', 'description'));

        if ($request->has('privileges')) {
            $role->privileges()->sync($request->privileges);
        }

        return response()->json($role->load('privileges'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
