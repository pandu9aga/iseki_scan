<?php

use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\McMiddleware;
use App\Http\Controllers\MainController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\RackController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\MonthlyController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\AdminSubmissionController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\MissingController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Controllers\User\RecordController;
use App\Http\Controllers\User\RequestController;
use App\Http\Controllers\User\SubmissionController;
use App\Http\Controllers\Mc\McRequestController;

use App\Models\Rack;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Route;
use Symfony\Component\Routing\RequestContext;

Route::get('/', [MainController::class, 'index'])->name('/');
Route::get('/login', [MainController::class, 'index'])->name('login');
Route::post('/login/auth', [MainController::class, 'login'])->name('login.auth');
Route::post('/login/member', [MainController::class, 'login_member'])->name('login.member');
Route::get('/logout', [MainController::class, 'logout'])->name('logout');
Route::get('/logout_member', [MainController::class, 'logout_member'])->name('logout.member');

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

    Route::get('/member', [MemberController::class, 'index'])->name('member');
    Route::get('/member/add', [MemberController::class, 'add'])->name('member.add');
    Route::post('/member/create', [MemberController::class, 'create'])->name('member.create');
    Route::get('/member/edit/{Id_Member}', [MemberController::class, 'edit'])->name('member.edit');
    Route::put('/member/update/{Id_Member}', [MemberController::class, 'update'])->name('member.update');
    Route::delete('/member/delete/{Id_Member}', [MemberController::class, 'destroy'])->name('member.destroy');

    Route::get('/admin_submission/submit', [AdminSubmissionController::class, 'submit'])->name('admin_submission.submit');
    Route::get('/admin_submission/export', [AdminSubmissionController::class, 'export'])->name('admin_submission.export');
    Route::post('/admin_submission/reset', [AdminSubmissionController::class, 'reset'])->name('admin_submission.reset');
    Route::get('/admin_submission', [AdminSubmissionController::class, 'index'])->name('admin_submission');


    Route::get('/admin_request', [AdminRequestController::class, 'index'])->name('admin_request');
    Route::get('/admin_request/submit', [AdminRequestController::class, 'submit'])->name('request.submit');
    Route::get('/admin_request/export', [AdminRequestController::class, 'export'])->name('request.export');
    Route::post('/admin_request/reset', [AdminRequestController::class, 'reset'])->name('admin_request.reset');
    
    Route::get('/missing', [MissingController::class, 'index'])->name('missing');
});

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/user_report', [UserReportController::class, 'index'])->name('user_report');
    Route::get('/user_report/submit', [UserReportController::class, 'submit'])->name('user_report.submit');
    Route::get('/user_report/export', [UserReportController::class, 'export'])->name('user_report.export');
    Route::put('/user_report/update/{id}', [UserReportController::class, 'update'])->name('user_report.update');
    Route::delete('user_report/{id}', [UserReportController::class, 'destroy'])->name('user_report.destroy');

    Route::get('/record', [RecordController::class, 'index'])->name('record');
    Route::post('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::get('/record/check', [RecordController::class, 'check'])->name('record.check');
    Route::post('/record/check-multiple', [RecordController::class, 'checkMultiple'])->name('record.checkMultiple');

    Route::get('/request', [RequestController::class, 'index'])->name('request');
    Route::post('/request/create', [RequestController::class, 'create'])->name('request.create');
    Route::get('/request/check', [RequestController::class, 'check'])->name('request.check');

    Route::get('/user_submission', [SubmissionController::class, 'index'])->name('submission');
    Route::get('/user_submission/submit', [SubmissionController::class, 'submit'])->name('user_submission.submit');
    Route::get('/submission/export', [SubmissionController::class, 'export'])->name('submission.export');
    Route::put('/submission/update/{id}', [SubmissionController::class, 'update'])->name('submission.update');
    Route::post('/user_submission/reset', [SubmissionController::class, 'reset'])->name('submission.reset');
    Route::delete('user_submission/{id}', [SubmissionController::class, 'destroy'])->name('submission.destroy'); 
});

Route::middleware(McMiddleware::class)->group(function () {
    Route::get('/mc_submission', [McRequestController::class, 'index'])->name('mc_submission');
    Route::get('/mc_submission/submit', [McRequestController::class, 'submit'])->name('mc_submission.submit');
    Route::get('/mc_submission/export', [McRequestController::class, 'export'])->name('mc_submission.export');
});

Route::post('/api/get-code-item', function(Request $request) {
    $codeRack = $request->input('code_rack');
    $rack = Rack::where('Code_Rack', $codeRack)->first();

    if ($rack) {
        return response()->json(['code_item' => $rack->Code_Item_Rack]);
    } else {
        return response()->json(['code_item' => null]);
    }
});

Route::get('/admin', [MainController::class, 'admin'])->name('admin');
Route::post('/admin/create', [MainController::class, 'create'])->name('admin.create');
