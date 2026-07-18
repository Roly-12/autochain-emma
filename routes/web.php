<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AlertController;
use App\Http\Controllers\Web\BlockchainTransactionController;
use App\Http\Controllers\Web\DocumentController;
use App\Http\Controllers\Web\FuelLogController;
use App\Http\Controllers\Web\MaintenanceController;
use App\Http\Controllers\Web\MileageController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\UserManagementController;
use App\Http\Controllers\Web\VehicleController;
use App\Http\Controllers\Web\VehicleSaleController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/mfa/verify', function () {
    return Inertia::render('Auth/MfaVerify');
})->name('mfa.verify');

Route::post('/mfa/verify', function (Request $request) {
    $request->validate(['code' => ['required', 'digits:6']]);

    if (now()->timestamp > (int) $request->session()->get('mfa_expires_at', 0)) {
        $request->session()->forget(['mfa_code', 'mfa_user_id', 'mfa_expires_at']);

        return redirect()->route('login')->withErrors([
            'email' => 'Le code MFA a expiré. Reconnectez-vous.',
        ]);
    }

    if ($request->session()->get('mfa_code') !== $request->input('code')) {
        return back()->withErrors(['code' => 'Le code est invalide.']);
    }

    $userId = $request->session()->get('mfa_user_id');

    if (! $userId) {
        return redirect()->route('login');
    }

    $user = \App\Models\User::find($userId);
    if (! $user?->is_active) {
        $request->session()->forget(['mfa_code', 'mfa_user_id', 'mfa_expires_at']);
        abort(403, 'Ce compte est désactivé.');
    }

    $request->session()->forget(['mfa_code', 'mfa_user_id', 'mfa_expires_at']);
    $request->session()->put('mfa_verified', true);

    Auth::loginUsingId($userId);

    return redirect()->intended(route('dashboard', absolute: false));
})->middleware('throttle:5,1')->name('mfa.verify.store');

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/services', [PageController::class, 'services'])->name('services');
Route::get('/contact', [PageController::class, 'contact'])->name('contact');

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', [PageController::class, 'dashboard'])->name('dashboard');

    Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
    Route::get('/vehicles/create', [VehicleController::class, 'create'])->name('vehicles.create');
    Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
    Route::get('/vehicles/{vehicle}', [VehicleController::class, 'show'])->name('vehicles.show');
    Route::get('/vehicles/{vehicle}/edit', [VehicleController::class, 'edit'])->name('vehicles.edit');
    Route::match(['put', 'patch', 'post'], '/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
    Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');
    Route::post('/vehicles/{vehicle}/photo', [VehicleController::class, 'updatePhoto'])->name('vehicles.photo');
    Route::post('/vehicles/{vehicle}/assign', [VehicleController::class, 'assign'])->name('vehicles.assign');
    Route::post('/vehicles/{vehicle}/status', [VehicleController::class, 'updateStatus'])->name('vehicles.status');
    Route::post('/vehicles/{vehicle}/mileage', [MileageController::class, 'store'])->name('vehicles.mileage');

    Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('/maintenance/create', [MaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::get('/maintenance/{maintenance}/edit', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
    Route::patch('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::delete('/maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

    Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index');
    Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create');
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/verify', [DocumentController::class, 'verify'])->name('documents.verify');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

    Route::get('/fuel', [FuelLogController::class, 'index'])->name('fuel.index');
    Route::get('/fuel/create', [FuelLogController::class, 'create'])->name('fuel.create');
    Route::post('/fuel', [FuelLogController::class, 'store'])->name('fuel.store');

    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/{alert}/resolve', [AlertController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/sales', [VehicleSaleController::class, 'index'])->name('sales.index');
    Route::get('/sales/create', [VehicleSaleController::class, 'create'])->name('sales.create');
    Route::post('/sales', [VehicleSaleController::class, 'store'])->name('sales.store');
    Route::post('/sales/{sale}/sign-buyer', [VehicleSaleController::class, 'signBuyer'])->name('sales.sign-buyer');
    Route::post('/sales/{sale}/cancel', [VehicleSaleController::class, 'cancel'])->name('sales.cancel');

    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
    Route::patch('/users/{user}', [UserManagementController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/garage-certification', [UserManagementController::class, 'setGarageCertification'])
        ->name('users.garage-certification');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/wallet', [WalletController::class, 'show'])->name('wallet.show');
    Route::post('/wallet/challenge', [WalletController::class, 'challenge'])
        ->middleware('throttle:10,1')
        ->name('wallet.challenge');
    Route::post('/wallet/verify', [WalletController::class, 'verify'])
        ->middleware('throttle:10,1')
        ->name('wallet.verify');
    Route::delete('/wallet', [WalletController::class, 'disconnect'])->name('wallet.disconnect');

    Route::get('/blockchain/transactions/{blockchainTransaction}', [BlockchainTransactionController::class, 'show'])
        ->name('blockchain.transactions.show');
    Route::post('/blockchain/transactions/{blockchainTransaction}/submit', [BlockchainTransactionController::class, 'submit'])
        ->middleware('throttle:20,1')
        ->name('blockchain.transactions.submit');
    Route::get('/blockchain/transactions/{blockchainTransaction}/status', [BlockchainTransactionController::class, 'status'])
        ->name('blockchain.transactions.status');
    Route::post('/blockchain/transactions/{blockchainTransaction}/retry', [BlockchainTransactionController::class, 'retry'])
        ->name('blockchain.transactions.retry');
});

require __DIR__.'/auth.php';
