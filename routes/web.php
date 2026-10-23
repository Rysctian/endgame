<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\ProjectWorkflowController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\WorkspaceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn (Request $request) => redirect($request->user() ? '/workspaces' : '/login'));
Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('auth/login'))->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', fn () => Inertia::render('auth/register'))->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:6,1');
    Route::get('/forgot-password', fn () => Inertia::render('auth/forgot-password'))->name('password.request');
    Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', fn (Request $request, string $token) => Inertia::render('auth/reset-password', ['token' => $token, 'email' => $request->query('email', '')]))->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
});
Route::middleware(['auth', 'active'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/email/verify', fn () => Inertia::render('auth/verify-email'))->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
    Route::post('/email/verification-notification', [AuthController::class, 'resend'])->middleware('throttle:6,1')->name('verification.send');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'password']);
    Route::middleware('verified')->group(function () {
        Route::post('/workspaces/{workspace}/invitations', [InvitationController::class, 'store'])->middleware('throttle:20,1');
        Route::delete('/workspaces/{workspace}/invitations/{invitation}', [InvitationController::class, 'destroy']);
        Route::get('/invitations/{invitation}/{token}', [InvitationController::class, 'show'])->name('invitations.show');
        Route::post('/invitations/{invitation}/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
        Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');
        Route::post('/workspaces', [WorkspaceController::class, 'store']);
        Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show'])->name('workspaces.show');
        Route::patch('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
        Route::post('/workspaces/{workspace}/roles', [RoleController::class, 'store']);
        Route::patch('/workspaces/{workspace}/roles/{role}', [RoleController::class, 'update']);
        Route::delete('/workspaces/{workspace}/roles/{role}', [RoleController::class, 'destroy']);
        Route::patch('/workspaces/{workspace}/members/{member}', [MemberController::class, 'update']);
        Route::delete('/workspaces/{workspace}/members/{member}', [MemberController::class, 'destroy']);
        Route::prefix('/workspaces/{workspace}/projects')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('projects.index');
            Route::post('/', [ProjectController::class, 'store'])->name('projects.store');
            Route::get('/{project}/tickets', [TicketController::class, 'index'])->name('tickets.index');
            Route::post('/{project}/tickets', [TicketController::class, 'store'])->name('tickets.store');
            Route::patch('/{project}/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
            Route::delete('/{project}/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');
            Route::post('/{project}/tickets/{ticket}/move', [TicketController::class, 'move'])->name('tickets.move');
            Route::get('/{project}/workflow', [ProjectWorkflowController::class, 'edit'])->name('workflow.edit');
            Route::put('/{project}/workflow', [ProjectWorkflowController::class, 'update'])->name('workflow.update');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('projects.show');
            Route::patch('/{project}', [ProjectController::class, 'update'])->name('projects.update');
            Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
            Route::post('/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
            Route::post('/{project}/members', [ProjectMemberController::class, 'store'])->name('projects.members.store');
            Route::delete('/{project}/members/{member}', [ProjectMemberController::class, 'destroy'])->name('projects.members.destroy');
        });
        Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users');
        Route::patch('/admin/users/{user}', [AdminUserController::class, 'update']);
    });
});
