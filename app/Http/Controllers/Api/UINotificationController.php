<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UINotification;
use Illuminate\Http\Request;

class UINotificationController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->company_id;

        $items = UINotification::where('company_id', $companyId)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $unread = UINotification::where('company_id', $companyId)
            ->where('status', 'unread')
            ->count();

        return response()->json([
            'unread' => $unread,
            'items' => $items
        ]);
    }

    public function markRead(Request $request, $id)
    {
        $companyId = $request->user()->company_id;

        $n = UINotification::where('company_id', $companyId)->findOrFail($id);
        $n->update(['status' => 'read']);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $companyId = $request->user()->company_id;

        UINotification::where('company_id', $companyId)
            ->where('status', 'unread')
            ->update(['status' => 'read']);

        return response()->json(['ok' => true]);
    }
}
