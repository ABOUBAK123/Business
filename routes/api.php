<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\WorkflowController;
use App\Http\Controllers\SignatureController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\AppSettingController;
use App\Http\Controllers\Admin\AdminController;

// Callback OnlyOffice — appelé directement par le serveur OO (pas un navigateur)
// Doit être en API (pas de session/CSRF/cookie middleware)
Route::post('/oo-callback/template/{templateId}', [AdminController::class, 'ooTemplateCallback'])
    ->name('oo.template.callback');
Route::post('/oo-callback/document/{document}', [DocumentController::class, 'onlyofficeCallback'])
    ->name('oo.document.callback');

// Auth API
Route::post('/login',  [AuthController::class, 'apiLogin']);
// /register désactivé : création de comptes réservée aux admins via le panel

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'apiLogout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Documents
    Route::apiResource('documents', DocumentController::class)->names([
        'index'   => 'api.documents.index',
        'store'   => 'api.documents.store',
        'show'    => 'api.documents.show',
        'update'  => 'api.documents.update',
        'destroy' => 'api.documents.destroy',
    ]);
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('api.documents.download');

    // Workflows
    Route::apiResource('workflows', WorkflowController::class)->names([
        'index'   => 'api.workflows.index',
        'store'   => 'api.workflows.store',
        'show'    => 'api.workflows.show',
        'update'  => 'api.workflows.update',
        'destroy' => 'api.workflows.destroy',
    ]);
    Route::post('/workflows/{workflow}/execute', [WorkflowController::class, 'execute'])->name('api.workflows.execute');

    // Signatures
    Route::get('/signatures',   [SignatureController::class, 'index']);
    Route::post('/signatures',  [SignatureController::class, 'store']);
    Route::post('/signatures/request', [SignatureController::class, 'request']);
    Route::post('/signatures/{signatureRequest}/decline', [SignatureController::class, 'decline']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Chat
    Route::get('/chat/messages', [ChatController::class, 'messages']);
    Route::post('/chat/send',    [ChatController::class, 'send']);

    // Settings
    Route::get('/settings/{key}', [AppSettingController::class, 'get']);
    Route::post('/settings',      [AppSettingController::class, 'update']);
});
