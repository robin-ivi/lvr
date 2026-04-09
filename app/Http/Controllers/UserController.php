<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return inertia('Users/Index', [
            'users' => \App\Models\User::with("roles")->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        
        return inertia('Users/Create', [
            'roles' => Role::pluck("name")->toArray()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $users = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $users->syncRoles($request->roles);
        return to_route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $user = \App\Models\User::findOrFail($id);
        return inertia('Users/Edit', [
            'user' => $user,
            'userRole' => $user->roles()->pluck("name")->all(),
            'roles' => Role::pluck("name")->all()
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
        ]);

        $user = \App\Models\User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->syncRoles($request->roles);
        return to_route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id) {
        \App\Models\User::destroy($id);
        return to_route('users.index')->with('success', 'User deleted successfully.');
    }
}
