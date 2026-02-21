<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\AgentController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\RdvController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UINotificationController;
use App\Http\Controllers\Api\WhatsAppController;
use App\Http\Controllers\Api\WorkerNotificationController;
use App\Http\Controllers\Api\SubscriptionController;

Route::post('/auth/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // dashboard (admin + agent)
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // ❌ subscription (admin only) —
    Route::middleware('role:admin')->group(function () {
        Route::get('/subscription', [SubscriptionController::class, 'show']);
    });

    /**
     * ✅ READ routes (admin + agent)
     */
    Route::get('/rdvs', [RdvController::class, 'index']);
    Route::get('/services', [ServiceController::class, 'index']);
    Route::get('/clients/{id}/history', [ClientController::class, 'history']);
    Route::get('/clients', [ClientController::class, 'index']);

    // ui notifications
    Route::get('/ui-notifications', [UINotificationController::class, 'index']);
    Route::post('/ui-notifications/{id}/read', [UINotificationController::class, 'markRead']);
    Route::post('/ui-notifications/read-all', [UINotificationController::class, 'markAllRead']);

    // whatsapp connect (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::get('/whatsapp/status', [WhatsAppController::class, 'status']);
        Route::get('/whatsapp/qr', [WhatsAppController::class, 'qr']);
    });

    /**
     * ✅ WRITE routes (requires subscription active)
     */
    Route::middleware('sub.active')->group(function () {

        // ✅ RDVs: admin + agent 
        Route::post('/rdvs', [RdvController::class, 'store']);
        Route::put('/rdvs/{id}', [RdvController::class, 'update']);
        Route::delete('/rdvs/{id}', [RdvController::class, 'destroy']);
        Route::post('/rdvs/recurring', [RdvController::class, 'storeRecurring']);

        // ✅ admin-only writes for services/clients
        Route::middleware('role:admin')->group(function () {

            // services
            Route::post('/services', [ServiceController::class, 'store']);
            Route::put('/services/{id}', [ServiceController::class, 'update']);
            Route::delete('/services/{id}', [ServiceController::class, 'destroy']);

            // clients import/export (admin only)
            Route::get('/clients/export', [ClientController::class, 'export']);
            Route::post('/clients/import', [ClientController::class, 'import']);

            // clients CRUD write (admin only)
            Route::post('/clients', [ClientController::class, 'store']);
            Route::put('/clients/{id}', [ClientController::class, 'update']);
            Route::delete('/clients/{id}', [ClientController::class, 'destroy']);

            // admin agents
            Route::get('/agents', [AgentController::class, 'index']);
            Route::post('/agents', [AgentController::class, 'store']);
            Route::put('/agents/{id}', [AgentController::class, 'update']);
            Route::delete('/agents/{id}', [AgentController::class, 'destroy']);
        });
    });

    // ✅ 
    Route::middleware('role:admin')->group(function () {
        Route::get('/agents', [AgentController::class, 'index']);
    });
});

// worker routes (no sanctum)
Route::middleware('worker.token')->group(function () {
    Route::get('/worker/notifications/pending', [WorkerNotificationController::class, 'pending']);
    Route::post('/worker/notifications/{id}/status', [WorkerNotificationController::class, 'updateStatus']);
});
