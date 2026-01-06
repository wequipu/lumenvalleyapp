<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
        ]);

        $role = Role::create($request->only('name', 'description'));

        if ($request->has('privileges')) {
            $role->privileges()->attach($request->privileges);
        }

        return response()->json($role->load('privileges'), 201);
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
            'name' => ['required', 'string', Rule::unique('roles')->ignore($role->id)],
            'description' => 'nullable|string',
        ]);

        $role->update($request->only('name', 'description'));

        if ($request->has('privileges')) {
            $role->privileges()->sync($request->privileges);
        }

        return $role->load('privileges');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->noContent();
    }

    /**
     * Assign privileges to a role.
     */
    public function assignPrivileges(Request $request, Role $role)
    {
        $request->validate([
            'privileges' => 'required|array',
            'privileges.*' => 'exists:privileges,id',
        ]);

        $role->privileges()->sync($request->privileges);

        return $role->load('privileges');
    }
}
