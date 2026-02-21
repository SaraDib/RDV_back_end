<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class WorkerNotificationController extends Controller
{
    public function pending()
    {
        $list = Notification::where('type', 'whatsapp')
            ->whereIn('status', ['pending','failed'])
            ->where('tries', '<', 5)
            ->orderBy('id')
            ->limit(20)
            ->get();

        return response()->json($list);
    }

    public function updateStatus(Request $request, $id)
    {
        $data = $request->validate([
            'status' => 'required|string|in:pending,sending,sent,failed',
            'error' => 'nullable|string'
        ]);

        $n = Notification::findOrFail($id);

        $tries = $n->tries;
        if ($data['status'] === 'failed') $tries = $tries + 1;

        $n->update([
            'status' => $data['status'],
            'error' => $data['error'] ?? null,
            'tries' => $tries,
        ]);

        return response()->json(['ok' => true]);
    }
}
