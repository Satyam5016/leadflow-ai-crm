<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AIAssistantController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CrmInteractionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DealController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::get('/forgot-password', fn () => Inertia::render('Auth/ForgotPassword'))->name('password.request');
    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        return back()->with('success', 'Password reset link sent if the account exists.');
    })->name('password.email');
    Route::get('/reset-password/{token}', fn (string $token, Request $request) => Inertia::render('Auth/ResetPassword', [
        'token' => $token,
        'email' => $request->email,
    ]))->name('password.reset');
    Route::post('/reset-password', function (Request $request) {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function ($user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
        });

        return redirect()->route('login')->with('success', 'Password reset. You can log in now.');
    })->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', fn () => Inertia::render('Auth/VerifyEmail'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard');
    })->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Verification link sent.');
    })->middleware('throttle:6,1')->name('verification.send');
    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/invitations/{invitation}/accept', [InvitationController::class, 'accept'])->name('invitations.accept');
});

Route::middleware(['auth', 'verified', 'workspace'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/workspaces/{workspace}/switch', [WorkspaceController::class, 'switch'])->name('workspaces.switch');
    Route::get('/workspaces/{workspace}/settings', [WorkspaceController::class, 'edit'])->name('workspaces.edit');
    Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::post('/workspaces/{workspace}/invitations', [InvitationController::class, 'store'])->name('invitations.store');

    Route::resource('leads', LeadController::class)->except(['create', 'edit']);
    Route::post('/leads/{lead}/convert', [LeadController::class, 'convert'])->name('leads.convert');
    Route::post('/leads/import', [LeadController::class, 'import'])->name('leads.import');
    Route::get('/leads-export', [LeadController::class, 'export'])->name('leads.export');
    Route::resource('customers', CustomerController::class)->except(['create', 'edit']);
    Route::resource('deals', DealController::class)->except(['create', 'edit']);
    Route::patch('/deals/{deal}/stage', [DealController::class, 'stage'])->name('deals.stage');
    Route::resource('tasks', TaskController::class)->except(['create', 'show', 'edit']);
    Route::post('/crm/notes', [CrmInteractionController::class, 'note'])->name('crm.notes.store');
    Route::post('/crm/emails', [CrmInteractionController::class, 'email'])->name('crm.emails.store');
    Route::post('/crm/files', [CrmInteractionController::class, 'file'])->name('crm.files.store');
    Route::get('/crm/files/{file}/download', [CrmInteractionController::class, 'download'])->name('crm.files.download');

    Route::get('/activity', ActivityController::class)->name('activity.index');
    Route::get('/reports', ReportController::class)->name('reports.index');
    Route::get('/ai-assistant', [AIAssistantController::class, 'index'])->name('ai.index');
    Route::post('/ai-assistant', [AIAssistantController::class, 'ask'])->name('ai.ask');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});
