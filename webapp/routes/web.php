<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', fn () => redirect()->route('admin.dashboard'));

Route::prefix('setup')->group(function (): void {
    Route::get('status', [\App\Http\Controllers\SetupController::class, 'status'])
        ->name('setup.status');
    Route::post('run', [\App\Http\Controllers\SetupController::class, 'run'])
        ->name('setup.run');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])
        ->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])
        ->name('logout');

    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])
            ->name('dashboard')->middleware('menu:dashboard');

        // Users management
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
            ->except(['show'])->middleware('menu:users');

        // News Source management (Reference Data)
        Route::resource('sources', \App\Http\Controllers\Admin\NewsSourceController::class)
            ->except(['show'])->middleware('menu:sources');
        Route::patch('sources/{source}/toggle', [\App\Http\Controllers\Admin\NewsSourceController::class, 'toggle'])
            ->name('sources.toggle')->middleware('menu:sources');
        Route::post('sources/{source}/test', [\App\Http\Controllers\Admin\NewsSourceController::class, 'testConnection'])
            ->name('sources.test')->middleware('menu:sources');
        Route::post('sources/{source}/fetch-now', [\App\Http\Controllers\Admin\NewsSourceController::class, 'fetchNow'])
            ->name('sources.fetch-now')->middleware('menu:sources');

        // Members management
        Route::resource('members', \App\Http\Controllers\Admin\MemberController::class)
            ->except(['show'])->middleware('menu:members');
        Route::patch('members/{member}/toggle', [\App\Http\Controllers\Admin\MemberController::class, 'toggle'])
            ->name('members.toggle')->middleware('menu:members');

        // Member channels / interests / schedules
        Route::get('members/{member}/channels', [\App\Http\Controllers\Admin\MemberChannelController::class, 'index'])
            ->name('members.channels.index')->middleware('menu:members');
        Route::post('members/{member}/channels', [\App\Http\Controllers\Admin\MemberChannelController::class, 'store'])
            ->name('members.channels.store')->middleware('menu:members');
        Route::patch('channels/{channel}', [\App\Http\Controllers\Admin\MemberChannelController::class, 'update'])
            ->name('members.channels.update')->middleware('menu:members');
        Route::delete('channels/{channel}', [\App\Http\Controllers\Admin\MemberChannelController::class, 'destroy'])
            ->name('members.channels.destroy')->middleware('menu:members');

        Route::get('members/{member}/interests', [\App\Http\Controllers\Admin\MemberInterestController::class, 'index'])
            ->name('members.interests.index')->middleware('menu:members');
        Route::post('members/{member}/interests', [\App\Http\Controllers\Admin\MemberInterestController::class, 'store'])
            ->name('members.interests.store')->middleware('menu:members');
        Route::delete('interests/{interest}', [\App\Http\Controllers\Admin\MemberInterestController::class, 'destroy'])
            ->name('members.interests.destroy')->middleware('menu:members');

        Route::get('members/{member}/schedules', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'index'])
            ->name('members.schedules.index')->middleware('menu:members');
        Route::post('members/{member}/schedules', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'store'])
            ->name('members.schedules.store')->middleware('menu:members');
        Route::get('schedules/{schedule}/edit', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'edit'])
            ->name('members.schedules.edit')->middleware('menu:members');
        Route::patch('schedules/{schedule}', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'update'])
            ->name('members.schedules.update')->middleware('menu:members');
        Route::delete('schedules/{schedule}', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'destroy'])
            ->name('members.schedules.destroy')->middleware('menu:members');
        Route::post('schedules/{schedule}/send-news', [\App\Http\Controllers\Admin\MemberScheduleController::class, 'sendNews'])
            ->name('members.schedules.send-news')->middleware('menu:members');

        // News search (Admin only)
        Route::get('news', [\App\Http\Controllers\Admin\NewsSearchController::class, 'index'])
            ->name('news.index')->middleware('menu:news');
        Route::post('news/destroy-many', [\App\Http\Controllers\Admin\NewsSearchController::class, 'destroyMany'])
            ->name('news.destroy-many')->middleware('menu:news');
        Route::post('news/destroy-by-filter', [\App\Http\Controllers\Admin\NewsSearchController::class, 'destroyByFilter'])
            ->name('news.destroy-by-filter')->middleware('menu:news');

        // Dashboard data endpoints (JSON, for charts)
        Route::get('dashboard/stats', [\App\Http\Controllers\Admin\DashboardController::class, 'stats'])
            ->name('dashboard.stats')->middleware('menu:dashboard');
        Route::get('dashboard/export', [\App\Http\Controllers\Admin\DashboardController::class, 'export'])
            ->name('dashboard.export')->middleware('menu:dashboard');

        // Credentials (system)
        Route::get('credentials', [\App\Http\Controllers\Admin\CredentialController::class, 'index'])
            ->name('credentials.index')->middleware('menu:credentials');
        Route::put('credentials/{credential}', [\App\Http\Controllers\Admin\CredentialController::class, 'update'])
            ->name('credentials.update')->middleware('menu:credentials');

        // Categories
        Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)
            ->except(['show'])->middleware('menu:categories');
    });

    Route::prefix('member')->name('member.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Member\ProfileController::class, 'index'])
            ->name('dashboard');
        Route::get('channels', [\App\Http\Controllers\Member\ProfileController::class, 'channels'])
            ->name('channels');
        Route::post('channels', [\App\Http\Controllers\Member\ProfileController::class, 'storeChannel'])
            ->name('channels.store');
        Route::get('interests', [\App\Http\Controllers\Member\ProfileController::class, 'interests'])
            ->name('interests');
        Route::post('interests', [\App\Http\Controllers\Member\ProfileController::class, 'storeInterest'])
            ->name('interests.store');
        Route::get('schedules', [\App\Http\Controllers\Member\ProfileController::class, 'schedules'])
            ->name('schedules');
        Route::post('schedules', [\App\Http\Controllers\Member\ProfileController::class, 'storeSchedule'])
            ->name('schedules.store');
    });

    Route::prefix('chat')->name('chat.')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\ChatController::class, 'index'])
            ->name('index')->middleware('menu:chat');
        Route::post('ask', [\App\Http\Controllers\ChatController::class, 'ask'])
            ->name('ask')->middleware('menu:chat');
    });
});
