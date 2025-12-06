<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AtivoTIController;
use App\Http\Controllers\DepartamentoController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ChamadoController;
use App\Http\Controllers\ProblemaController;
use App\Http\Controllers\NotificationController as NotifController; 



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth', 'verified'])->group(function () {

    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.show'); 
    Route::get('/profile/edit', [ProfileController::class, 'editProfile'])->name('profile.edit'); 
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotifController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');
    Route::get('/notifications', [NotificationController::class, 'getNotifications'])->name('notifications.all');
    Route::get('/notifications/count', [NotificationController::class, 'getUnreadCount'])->name('notifications.count');
    Route::post('/notifications/mark-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::patch('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');



    // --- MÓDULOS PRINCIPAIS ---

    Route::get('meus-chamados', [ChamadoController::class, 'myChamados'])->name('chamados.my');
    Route::get('chamados-fechados', [ChamadoController::class, 'closedIndex'])->name('chamados.closed');
    Route::get('chamados/{chamado}/report', [ChamadoController::class, 'generateReport'])->name('chamados.report');
    Route::post('chamados/{chamado}/updates', [ChamadoController::class, 'addUpdate'])->name('chamados.updates.store');
    Route::patch('chamados/{chamado}/status', [ChamadoController::class, 'updateStatus'])->name('chamados.updateStatus');
    Route::patch('chamados/{chamado}/assign', [ChamadoController::class, 'assignToSelf'])->name('chamados.assign');
    Route::patch('chamados/{chamado}/attend', [ChamadoController::class, 'attend'])->name('chamados.attend');
    Route::patch('chamados/{chamado}/resolve', [ChamadoController::class, 'resolve'])->name('chamados.resolve');
    Route::patch('chamados/{chamado}/escalate', [ChamadoController::class, 'escalate'])->name('chamados.escalate');
    Route::patch('chamados/{chamado}/atribuir', [ChamadoController::class, 'atribuir'])->name('chamados.atribuir');
    Route::patch('chamados/{chamado}/close', [ChamadoController::class, 'close'])->name('chamados.close');
    Route::patch('chamados/{chamado}/reopen', [ChamadoController::class, 'reopen'])->name('chamados.reopen');
    Route::resource('chamados', ChamadoController::class)->only(['index', 'create', 'store', 'show']);

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');


    Route::resource('problemas', ProblemaController::class)->only(['create', 'store']);

    // --- ROTAS ADMINISTRATIVAS (Protegidas por Cargo) ---
    Route::middleware(['role:Admin|Supervisor'])->group(function () {
        Route::resource('users', UserController::class)
    ->only(['index', 'create', 'store', 'edit', 'update']);
        
        Route::resource('departamentos', DepartamentoController::class)->parameters(['departamentos' => 'departamento']);

        Route::resource('categorias', CategoriaController::class);
    });
    

});


require __DIR__.'/auth.php';