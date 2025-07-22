<?php

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Controllers\MainController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\RackController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MonthlyController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Controllers\User\RecordController;
use Illuminate\Support\Facades\Route;

Route::get('/check', function () {
    return response()->json([
        'Id_Type_User' => session('Id_Type_User')
    ]);
});

Route::get('/', [MainController::class, 'index'])->name('/');
Route::get('/login', [MainController::class, 'index'])->name('login');
Route::post('/login/auth', [MainController::class, 'login'])->name('login.auth');
Route::get('/logout', [MainController::class, 'logout'])->name('logout');

Route::middleware(AdminMiddleware::class)->group(function () {
    Route::get('/dashboard', [AdminController::class, 'index'])->name('dashboard');

    Route::get('/item', [ItemController::class, 'index'])->name('item');
    Route::get('/item/add', [ItemController::class, 'add'])->name('item.add');
    Route::post('/item/create', [ItemController::class, 'create'])->name('item.create');
    Route::get('/item/edit/{Id_Item}', [ItemController::class, 'edit'])->name('item.edit');
    Route::put('/item/update/{Id_Item}', [ItemController::class, 'update'])->name('item.update');
    Route::delete('/item/delete/{Id_Item}', [ItemController::class, 'destroy'])->name('item.destroy');

    Route::get('/rack', [RackController::class, 'index'])->name('rack');
    Route::get('/rack/add', [RackController::class, 'add'])->name('rack.add');
    Route::post('/rack/create', [RackController::class, 'create'])->name('rack.create');
    Route::get('/rack/edit/{Id_Rack}', [RackController::class, 'edit'])->name('rack.edit');
    Route::put('/rack/update/{Id_Rack}', [RackController::class, 'update'])->name('rack.update');
    Route::delete('/rack/delete/{Id_Rack}', [RackController::class, 'destroy'])->name('rack.destroy');
    Route::get('/rack/upload', [RackController::class, 'upload'])->name('rack.upload');
    Route::post('/rack/import', [RackController::class, 'import'])->name('rack.import');
    Route::get('/rack/export', [RackController::class, 'export'])->name('rack.export');

    Route::get('/user', [UserController::class, 'index'])->name('user');
    Route::get('/user/add', [UserController::class, 'add'])->name('user.add');
    Route::post('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::get('/user/edit/{Id_User}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/update/{Id_User}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/delete/{Id_User}', [UserController::class, 'destroy'])->name('user.destroy');

    Route::get('/report', [ReportController::class, 'index'])->name('report');
    Route::get('/report/submit', [ReportController::class, 'submit'])->name('report.submit');
    Route::get('/report/export', [ReportController::class, 'export'])->name('report.export');

    Route::get('/monthly', [MonthlyController::class, 'index'])->name('monthly');
    Route::get('/monthly/export', [MonthlyController::class, 'export'])->name('monthly.export');
    Route::get('/monthly/reset', [MonthlyController::class, 'reset'])->name('monthly.reset');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/user_report', [UserReportController::class, 'index'])->name('user_report');
    Route::get('/user_report/submit', [UserReportController::class, 'submit'])->name('user_report.submit');
    Route::get('/user_report/export', [UserReportController::class, 'export'])->name('user_report.export');

    Route::get('/record', [RecordController::class, 'index'])->name('record');
    Route::post('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::get('/record/check', [RecordController::class, 'check'])->name('record.check');
});

Route::get('/admin', [MainController::class, 'admin'])->name('admin');
Route::post('/admin/create', [MainController::class, 'create'])->name('admin.create');