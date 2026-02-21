<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AgentController extends Controller
{
    public function index(Request $request)
    {
        return User::where('company_id', $request->user()->company_id)
            ->where('role', 'agent')
            ->orderBy('name')
            ->get(['id','name','email','role','is_active','company_id','created_at']);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255', 'unique:users,email'],
            'password' => ['required','string','min:6'],
        ]);

        $agent = User::create([
            'company_id' => $request->user()->company_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'agent',
            'is_active' => true,
        ]);

        return response()->json($agent, 201);
    }

    public function update(Request $request, $id)
    {
        $agent = User::where('company_id', $request->user()->company_id)
            ->where('role', 'agent')
            ->findOrFail($id);

        $data = $request->validate([
            'name' => ['sometimes','required','string','max:255'],
            'email' => ['sometimes','required','email','max:255', Rule::unique('users','email')->ignore($agent->id)],
            'password' => ['nullable','string','min:6'],
            'is_active' => ['sometimes','boolean'],
        ]);

        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $agent->update($data);

        return response()->json($agent);
    }

    public function destroy(Request $request, $id)
    {
        $agent = User::where('company_id', $request->user()->company_id)
            ->where('role', 'agent')
            ->findOrFail($id);

        // safer: deactivate instead of delete
        $agent->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }
}
