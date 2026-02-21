<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        return Service::where('company_id', $request->user()->company_id)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'duration_min' => 'required|integer|min:5|max:480',
            'description' => 'nullable|string',
        ]);

        $data['company_id'] = $request->user()->company_id;
        $data['is_active'] = true;

        $service = Service::create($data);

        return response()->json($service, 201);
    }

    public function update(Request $request, $id)
    {
        $service = Service::where('company_id', $request->user()->company_id)->findOrFail($id);

        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'duration_min' => 'sometimes|required|integer|min:5|max:480',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        $service->update($data);

        return response()->json($service);
    }

    public function destroy(Request $request, $id)
    {
        $service = Service::where('company_id', $request->user()->company_id)->findOrFail($id);

        
        $service->update(['is_active' => false]);

        return response()->json(['ok' => true]);
    }
}
