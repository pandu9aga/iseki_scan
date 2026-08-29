<?php

use App\Http\Controllers\Admin\AchievementController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\AdminRequestController;
use App\Http\Controllers\Admin\AdminRequestingController;
use App\Http\Controllers\Admin\AdminRecordingController;
use App\Http\Controllers\Admin\AdminWithdrawalController;
use App\Http\Controllers\Admin\AdminStockItemController;
use App\Http\Controllers\Admin\AdminCheckController;
use App\Http\Controllers\Admin\AdminSubmissionController;
use App\Http\Controllers\Admin\ForgotController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\Admin\MissingController;
use App\Http\Controllers\Admin\MistakeController;
use App\Http\Controllers\Admin\MonthlyController;
use App\Http\Controllers\Admin\RackController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SumHistoryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ValidationController;
use App\Http\Controllers\Area\AreaScanController;
use App\Http\Controllers\Helper\UrgentController;
use App\Http\Controllers\Helper\SumController;
use App\Http\Controllers\Helper\WaQueueController;
use App\Http\Controllers\MainController;
use App\Http\Controllers\Mc\McAchievementController;
use App\Http\Controllers\Mc\McForgotController;
use App\Http\Controllers\Mc\McMissingController;
use App\Http\Controllers\Mc\McMistakeController;
use App\Http\Controllers\Mc\McRequestController;
use App\Http\Controllers\Mc\McValidationController;
use App\Http\Controllers\Transit\TransitScanController;
use App\Http\Controllers\User\HomeController;
use App\Http\Controllers\User\LabelControlller;
use App\Http\Controllers\User\RecordController;
use App\Http\Controllers\User\UserMissingController;
use App\Http\Controllers\User\RequestController;
use App\Http\Controllers\User\SubmissionController;
use App\Http\Controllers\User\UserWithdrawalController;
use App\Http\Controllers\User\UserCheckController;
use App\Http\Controllers\User\UserAchievementController;
use App\Http\Controllers\User\UserForgotController;
use App\Http\Controllers\User\UserMistakeController;
use App\Http\Controllers\User\UserReportController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\AnyAuthMiddleware;
use App\Http\Middleware\AreaMiddleware;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\McMiddleware;
use App\Http\Middleware\QcMiddleware;
use App\Http\Middleware\TransitMiddleware;
use App\Models\Rack;
use App\Http\Controllers\Qc\WithdrawalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    Route::get('/rack/type', [RackController::class, 'type'])->name('rack.type');
    Route::get('/rack/type/edit/{Id_Rack}', [RackController::class, 'typeEdit'])->name('rack.type.edit');
    Route::put('/rack/type/update/{Id_Rack}', [RackController::class, 'typeUpdate'])->name('rack.type.update');
    Route::get('/rack/type/upload', [RackController::class, 'typeUpload'])->name('rack.type.upload');
    Route::post('/rack/type/import', [RackController::class, 'typeImport'])->name('rack.type.import');
    Route::get('/rack/type/export', [RackController::class, 'typeExport'])->name('rack.type.export');

    Route::get('/user', [UserController::class, 'index'])->name('user');
    Route::get('/user/add', [UserController::class, 'add'])->name('user.add');
    Route::post('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::get('/user/edit/{Id_User}', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/update/{Id_User}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/delete/{Id_User}', [UserController::class, 'destroy'])->name('user.destroy');

    Route::get('/report', [ReportController::class, 'index'])->name('report');
    Route::get('/report/submit', [ReportController::class, 'submit'])->name('report.submit');
    Route::get('/report/export', [ReportController::class, 'export'])->name('report.export');
    Route::get('/report/ready-waiting', [ReportController::class, 'readyWaiting'])->name('report.ready_waiting');
    Route::get('/report/ready-waiting/export', [ReportController::class, 'readyWaitingExport'])->name('report.ready_waiting.export');

    Route::get('/monthly', [MonthlyController::class, 'index'])->name('monthly');
    Route::get('/monthly/export', [MonthlyController::class, 'export'])->name('monthly.export');
    Route::get('/monthly/reset', [MonthlyController::class, 'reset'])->name('monthly.reset');

    Route::get('/validation', [ValidationController::class, 'index'])->name('validation');
    Route::get('/validation/submit', [ValidationController::class, 'submit'])->name('validation.submit');
    Route::get('/validation/export', [ValidationController::class, 'export'])->name('validation.export');

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
    Route::get('/admin_request/search', [AdminRequestController::class, 'search'])->name('request.search');
    Route::get('/admin_request/export-search', [AdminRequestController::class, 'exportSearch'])->name('request.export_search');
    Route::post('/admin_request/reset', [AdminRequestController::class, 'reset'])->name('admin_request.reset');

    Route::get('/missing', [MissingController::class, 'index'])->name('missing');
    Route::get('/missing/export', [MissingController::class, 'export'])->name('missing.export');
    Route::get('/missing_mc', [MissingController::class, 'missing_mc'])->name('missing.mc');
    Route::get('/missing_mc/export', [MissingController::class, 'missing_mc_export'])->name('missing.mc.export');
    Route::get('/missing_estimation', [MissingController::class, 'missing_estimation'])->name('admin.missing.estimation');
    Route::get('/missing_estimation/export', [MissingController::class, 'missing_estimation_export'])->name('admin.missing.estimation.export');

    Route::get('/oke_estimation', [MissingController::class, 'oke_estimation'])->name('admin.oke.estimation');
    Route::get('/oke_estimation/export', [MissingController::class, 'oke_estimation_export'])->name('admin.oke.estimation.export');

    Route::get('/achievement', [AchievementController::class, 'index'])->name('achievement');
    Route::get('/achievement/export', [AchievementController::class, 'export'])->name('achievement.export');

    // Mistake Routes
    Route::get('/mistake', [MistakeController::class, 'index'])->name('mistake');
    Route::get('/mistake/add', [MistakeController::class, 'add'])->name('mistake.add');
    Route::post('/mistake/store', [MistakeController::class, 'store'])->name('mistake.store');
    Route::get('/mistake/get-latest-request', [MistakeController::class, 'getLatestRequest'])->name('mistake.get_latest_request');
    Route::get('/mistake/detail', [MistakeController::class, 'detail'])->name('mistake.detail');
    Route::get('/mistake/export', [MistakeController::class, 'export'])->name('mistake.export');

    // Forgot Routes
    Route::get('/forgot', [ForgotController::class, 'index'])->name('forgot');
    Route::get('/forgot/add', [ForgotController::class, 'add'])->name('forgot.add');
    Route::post('/forgot/store', [ForgotController::class, 'store'])->name('forgot.store');
    Route::get('/forgot/get-latest-request', [ForgotController::class, 'getLatestRequest'])->name('forgot.get_latest_request');
    Route::get('/forgot/detail', [ForgotController::class, 'detail'])->name('forgot.detail');
    Route::get('/forgot/export', [ForgotController::class, 'export'])->name('forgot.export');

    // Prediction Routes
    Route::get('/prediction/error', [\App\Http\Controllers\Admin\PredictionController::class, 'index'])->name('prediction.error');
    Route::get('/prediction/emptiness', [\App\Http\Controllers\Admin\PredictionController::class, 'emptiness'])->name('prediction.emptiness');

    Route::get('/admin_urgents', [UrgentController::class, 'index'])->name('admin.urgents');
    Route::get('/admin_sum', [SumController::class, 'index'])->name('admin.sum');

    // Sum History (selisih request vs datang)
    Route::get('/admin_sum_history', [SumHistoryController::class, 'index'])->name('admin.sumhistory');
    Route::get('/api/sum-history/data', [SumHistoryController::class, 'getData'])->name('sumhistory.data');
    Route::get('/api/sum-history/export', [SumHistoryController::class, 'export'])->name('sumhistory.export');

    // WA Queue Monitoring
    Route::get('/wa-queue', [WaQueueController::class, 'index'])->name('wa.queue');

    // Admin Requesting (scan barcode to create request)
    Route::get('/admin_requesting', [AdminRequestingController::class, 'index'])->name('admin.requesting');
    Route::post('/admin_requesting/create', [AdminRequestingController::class, 'create'])->name('admin.requesting.create');
    Route::get('/admin_requesting/check', [AdminRequestingController::class, 'check'])->name('admin.requesting.check');
    Route::post('/admin_requesting/check-duplicate', [AdminRequestingController::class, 'checkDuplicate'])->name('admin.requesting.checkDuplicate');
    Route::get('/admin_requesting/data', [AdminRequestingController::class, 'getData'])->name('admin.requesting.data');

    // Admin Recording (scan barcode to create record)
    Route::get('/admin_recording', [AdminRecordingController::class, 'index'])->name('admin.recording');
    Route::post('/admin_recording/create', [AdminRecordingController::class, 'create'])->name('admin.recording.create');
    Route::get('/admin_recording/check', [AdminRecordingController::class, 'check'])->name('admin.recording.check');
    Route::post('/admin_recording/check-multiple', [AdminRecordingController::class, 'checkMultiple'])->name('admin.recording.checkMultiple');
    Route::get('/admin_recording/data', [AdminRecordingController::class, 'getData'])->name('admin.recording.data');

    // Admin Withdrawal (with actions)
    Route::get('/admin_withdrawal', [AdminWithdrawalController::class, 'index'])->name('admin.withdrawal');
    Route::post('/admin_withdrawal/oke/{id}', [AdminWithdrawalController::class, 'oke'])->name('admin.withdrawal.oke');
    Route::post('/admin_withdrawal/arrive/{id}', [AdminWithdrawalController::class, 'arrive'])->name('admin.withdrawal.arrive');
    Route::post('/admin_withdrawal/finish/{id}', [AdminWithdrawalController::class, 'finish'])->name('admin.withdrawal.finish');
    Route::post('/admin_withdrawal/return/{id}', [AdminWithdrawalController::class, 'returnRack'])->name('admin.withdrawal.return');
    Route::delete('/admin_withdrawal/destroy/{id}', [AdminWithdrawalController::class, 'destroy'])->name('admin.withdrawal.destroy');
    Route::get('/admin_withdrawal/export', [AdminWithdrawalController::class, 'export'])->name('admin.withdrawal.export');

    // Admin Check
    Route::get('/admin_check', [AdminCheckController::class, 'index'])->name('admin.check');
    Route::get('/admin_check/export', [AdminCheckController::class, 'export'])->name('admin.check.export');
    Route::post('/admin_check/store', [AdminCheckController::class, 'store'])->name('admin.check.store');

    // Stock Item
    Route::get('/stock_item', [AdminStockItemController::class, 'index'])->name('stock_item');
    Route::post('/stock_item/store', [AdminStockItemController::class, 'store'])->name('stock_item.store');
    Route::post('/stock_item/import', [AdminStockItemController::class, 'import'])->name('stock_item.import');
    Route::get('/stock_item/export', [AdminStockItemController::class, 'export'])->name('stock_item.export');
    Route::get('/stock_item/template', [AdminStockItemController::class, 'downloadTemplate'])->name('stock_item.template');
    Route::delete('/stock_item/delete/{id}', [AdminStockItemController::class, 'destroy'])->name('stock_item.destroy');
    Route::post('/stock_item/bulk-destroy', [AdminStockItemController::class, 'bulkDestroy'])->name('stock_item.bulk_destroy');
    Route::post('/admin_check/{id}/done', [AdminCheckController::class, 'markAsDone'])->name('admin.check.done');
});

// WA Queue API (accessible without auth for cross-device JS calls)
Route::get('/api/wa-queue/fetch', [WaQueueController::class, 'fetch'])->name('wa.queue.fetch');
Route::delete('/api/wa-queue/{id}', [WaQueueController::class, 'destroy'])->name('wa.queue.cancel');
Route::patch('/api/wa-queue/{id}/sent', [WaQueueController::class, 'markSent'])->name('wa.queue.sent');
Route::patch('/api/wa-queue/{id}/failed', [WaQueueController::class, 'markFailed'])->name('wa.queue.failed');

Route::middleware(AuthMiddleware::class)->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::get('/user_oke_estimation', [UserMissingController::class, 'oke_estimation'])->name('user.oke.estimation');
    Route::get('/user_oke_estimation/export', [UserMissingController::class, 'oke_estimation_export'])->name('user.oke.estimation.export');

    Route::get('/user_report', [UserReportController::class, 'index'])->name('user_report');
    Route::get('/user_report/submit', [UserReportController::class, 'submit'])->name('user_report.submit');
    Route::get('/user_report/export', [UserReportController::class, 'export'])->name('user_report.export');
    Route::delete('user_report/{id}', [UserReportController::class, 'destroy'])->name('user_report.destroy');

    Route::get('/record', [RecordController::class, 'index'])->name('record');
    Route::post('/record/create', [RecordController::class, 'create'])->name('record.create');
    Route::get('/record/check', [RecordController::class, 'check'])->name('record.check');
    Route::post('/record/check-multiple', [RecordController::class, 'checkMultiple'])->name('record.checkMultiple');
    Route::get('/record/data', [RecordController::class, 'getData'])->name('record.data');
    Route::post('/record/{id}/update-sum', [RecordController::class, 'updateSum'])->name('record.updateSum');

    Route::get('/request', [RequestController::class, 'index'])->name('request');
    Route::post('/request/create', [RequestController::class, 'create'])->name('request.create');
    Route::get('/request/check', [RequestController::class, 'check'])->name('request.check');
    Route::post('/request/check-duplicate', [RequestController::class, 'checkDuplicate'])->name('request.checkDuplicate');
    Route::get('/request/data', [RequestController::class, 'getData'])->name('request.data');

    Route::prefix('label')->group(function () {
        Route::get('/', [LabelControlller::class, 'index'])->name('member.label.index');
        Route::post('/store', [LabelControlller::class, 'store'])->name('member.label.store');
        Route::get('/search-rack-part', [LabelControlller::class, 'searchRackPart'])->name('member.label.search');
        Route::post('/print-now', [LabelControlller::class, 'printNow'])->name('member.label.printNow');
    });

    Route::get('/user_submission', [SubmissionController::class, 'index'])->name('submission');
    Route::get('/user_submission/submit', [SubmissionController::class, 'submit'])->name('user_submission.submit');
    Route::get('/user_submission/export', [SubmissionController::class, 'export'])->name('submission.export');
    Route::get('/user_submission/search', [SubmissionController::class, 'search'])->name('submission.search');
    Route::put('/submission/update/{id}', [SubmissionController::class, 'update'])->name('submission.update');
    Route::post('/user_submission/reset', [SubmissionController::class, 'reset'])->name('submission.reset');
    Route::delete('user_submission/{id}', [SubmissionController::class, 'destroy'])->name('submission.destroy');

    Route::get('/user_mistake', [UserMistakeController::class, 'index'])->name('user_mistake');
    Route::get('/user_achievement', [UserAchievementController::class, 'index'])->name('user_achievement');
    Route::get('/user_forgot', [UserForgotController::class, 'index'])->name('user_forgot');

    Route::get('/user_urgents', [UrgentController::class, 'index'])->name('user.urgents');
    Route::get('/user_urgents/scan', [UrgentController::class, 'scan'])->name('user.urgents.scan');
    Route::post('/user_urgents/scan/process', [UrgentController::class, 'processScan'])->name('user.urgents.process');
    Route::get('/user_sum', [SumController::class, 'index'])->name('sum');

    // Member Withdrawal
    Route::get('/user_withdrawal', [UserWithdrawalController::class, 'index'])->name('user.withdrawal');
    Route::post('/user_withdrawal/oke/{id}', [UserWithdrawalController::class, 'oke'])->name('user.withdrawal.oke');
    Route::post('/user_withdrawal/arrive/{id}', [UserWithdrawalController::class, 'arrive'])->name('user.withdrawal.arrive');
    Route::post('/user_withdrawal/return/{id}', [UserWithdrawalController::class, 'returnRack'])->name('user.withdrawal.return');
    Route::get('/user_withdrawal/export', [UserWithdrawalController::class, 'export'])->name('user.withdrawal.export');

    // User Check
    Route::get('/user_check', [UserCheckController::class, 'index'])->name('user.check');
    Route::post('/user_check/store', [UserCheckController::class, 'store'])->name('user.check.store');
    Route::post('/user_check/auto-store', [UserCheckController::class, 'autoStore'])->name('user.check.auto_store');
    Route::post('/user_check/{id}/done', [UserCheckController::class, 'markAsDone'])->name('user.check.done');
});

Route::middleware(McMiddleware::class)->group(function () {
    Route::get('/mc_submission', [McRequestController::class, 'index'])->name('mc_submission');
    Route::get('/mc_submission/submit', [McRequestController::class, 'submit'])->name('mc_submission.submit');
    Route::get('/mc_submission/export', [McRequestController::class, 'export'])->name('mc_submission.export');
    Route::get('/mc_submission/search', [McRequestController::class, 'search'])->name('mc_submission.search');
    Route::get('/mc_submission/export-search', [McRequestController::class, 'exportSearch'])->name('mc_submission.export_search');

    Route::get('/mc_validation', [McValidationController::class, 'index'])->name('mc_validation');
    Route::get('/mc_validation/submit', [McValidationController::class, 'submit'])->name('mc_validation.submit');
    Route::get('/mc_validation/export', [McValidationController::class, 'export'])->name('mc_validation.export');

    Route::get('/mc_validate', [McValidationController::class, 'validate'])->name('mc.validate');
    Route::post('/mc_validate/check-rack', [McValidationController::class, 'checkRack'])->name('mc.validate.check.rack');
    Route::post('/mc_validate/check-requests', [McValidationController::class, 'checkRequests'])->name('mc.validate.check.requests');
    Route::post('/mc_validate', [McValidationController::class, 'store'])->name('mc.validate.store');

    Route::get('/mc_missing', [McMissingController::class, 'index'])->name('mc.missing');
    Route::get('/mc_missing/export', [McMissingController::class, 'export'])->name('mc.missing.export');
    Route::get('/mc_missing_mc', [McMissingController::class, 'missing_mc'])->name('mc.missing.mc');
    Route::get('/mc_missing_mc/export', [McMissingController::class, 'missing_mc_export'])->name('mc.missing.mc.export');
    Route::post('/mc_submission/upload-ready', [McRequestController::class, 'uploadReady'])->name('mc_submission.upload_ready');
    Route::post('/mc_submission/ok-stock/{id}', [McRequestController::class, 'okStock'])->name('mc_submission.ok_stock');
    Route::post('/mc_submission/no-stock/{id}', [McRequestController::class, 'noStock'])->name('mc_submission.no_stock');
    Route::post('/mc_submission/stock-shipping/{id}', [McRequestController::class, 'updateStockShipping'])->name('mc_submission.stock_shipping');

    Route::get('/mc_missing_estimation', [McMissingController::class, 'missing_estimation'])->name('mc.missing.estimation');
    Route::get('/mc_missing_estimation/export', [McMissingController::class, 'missing_estimation_export'])->name('mc.missing.estimation.export');

    Route::get('/mc_oke_estimation', [McMissingController::class, 'oke_estimation'])->name('mc.oke.estimation');
    Route::get('/mc_oke_estimation/export', [McMissingController::class, 'oke_estimation_export'])->name('mc.oke.estimation.export');

    Route::get('/mc_mistake', [McMistakeController::class, 'index'])->name('mc_mistake');
    Route::get('/mc_achievement', [McAchievementController::class, 'index'])->name('mc_achievement');
    Route::get('/mc_forgot', [McForgotController::class, 'index'])->name('mc_forgot');

    Route::get('/mc_urgents', [UrgentController::class, 'index'])->name('mc.urgents');
    Route::get('/mc_sum', [SumController::class, 'index'])->name('mc.sum');
});

Route::middleware(TransitMiddleware::class)->group(function () {
    Route::get('/transit/scan', [TransitScanController::class, 'index'])->name('transit.scan');
    Route::post('/transit/scan/process', [TransitScanController::class, 'process'])->name('transit.scan.process');
    Route::get('/transit/scan/check', [TransitScanController::class, 'check'])->name('transit.scan.check');
    Route::get('/transit/request', [\App\Http\Controllers\Transit\TransitRequestController::class, 'index'])->name('transit.request');
    Route::get('/transit/request/submit', [\App\Http\Controllers\Transit\TransitRequestController::class, 'submit'])->name('transit.request.submit');
    Route::get('/transit/request/export', [\App\Http\Controllers\Transit\TransitRequestController::class, 'export'])->name('transit.request.export');
    Route::get('/transit/request/search', [\App\Http\Controllers\Transit\TransitRequestController::class, 'search'])->name('transit.request.search');
});

Route::post('/api/get-code-item', function (Request $request) {
    $codeRack = $request->input('code_rack');
    $rack = Rack::where('Code_Rack', $codeRack)->first();

    if ($rack) {
        return response()->json([
            'code_item' => $rack->Code_Item_Rack,
            'type_tractor' => $rack->Type_Tractor_Rack,
        ]);
    } else {
        return response()->json([
            'code_item' => null,
            'type_tractor' => null,
        ]);
    }
});

Route::get('/api/urgents/data', [UrgentController::class, 'getData'])->name('urgents.data');
Route::get('/urgents/export', [UrgentController::class, 'export'])->name('urgents.export');
Route::get('/api/urgents/recap', [UrgentController::class, 'getRecapData'])->name('urgents.recap');

// Stock Item Check API (accessible from admin & member request pages)
Route::post('/api/check-stock-item', function (Request $request) {
    $codeRack = $request->input('code_rack');
    $exists = \App\Models\StockItem::where('Code_Rack_Stock_Item', $codeRack)->exists();
    return response()->json(['in_stock' => $exists]);
})->name('api.check_stock_item');

Route::get('/urgents/unrecorded', [UrgentController::class, 'unrecordedIndex'])->name('urgents.unrecorded');
Route::get('/api/urgents/unrecorded-data', [UrgentController::class, 'getUnrecordedData'])->name('urgents.unrecorded.data');
Route::get('/urgents/unrecorded/export', [UrgentController::class, 'exportUnrecorded'])->name('urgents.unrecorded.export');

// Sum (Part Sum Not Match) - shared across member/internal user roles
Route::middleware(AnyAuthMiddleware::class)->group(function () {
    Route::get('/api/sum/data', [SumController::class, 'getData'])->name('sum.data');
    Route::post('/sum/ready/{id}', [SumController::class, 'ready'])->name('sum.ready');
    Route::post('/sum/cancel/{id}', [SumController::class, 'cancel'])->name('sum.cancel');
});

Route::get('/admin', [MainController::class, 'admin'])->name('admin');
Route::post('/admin/create', [MainController::class, 'create'])->name('admin.create');

Route::post('/api/marshalling-empty', [AreaScanController::class, 'marshallingEmpty']);

Route::middleware(AreaMiddleware::class)->group(function () {
    Route::get('/area/scan', [AreaScanController::class, 'index'])->name('area.scan');
    Route::post('/area/scan/process', [AreaScanController::class, 'process'])->name('area.scan.process');
    Route::get('/area/urgents', [UrgentController::class, 'index'])->name('area.urgents');
});

Route::middleware(QcMiddleware::class)->group(function () {
    Route::get('/qc/withdrawal', [WithdrawalController::class, 'index'])->name('qc.withdrawal');
    Route::get('/qc/withdrawal/search-rack', [WithdrawalController::class, 'searchRack'])->name('qc.withdrawal.searchRack');
    Route::post('/qc/withdrawal/store', [WithdrawalController::class, 'store'])->name('qc.withdrawal.store');
    Route::post('/qc/withdrawal/oke/{id}', [WithdrawalController::class, 'oke'])->name('qc.withdrawal.oke');
    Route::post('/qc/withdrawal/receiving/{id}', [WithdrawalController::class, 'receiving'])->name('qc.withdrawal.receiving');
    Route::post('/qc/withdrawal/finish/{id}', [WithdrawalController::class, 'finish'])->name('qc.withdrawal.finish');
    Route::post('/qc/withdrawal/return/{id}', [WithdrawalController::class, 'returnRack'])->name('qc.withdrawal.return');
    Route::delete('/qc/withdrawal/destroy/{id}', [WithdrawalController::class, 'destroy'])->name('qc.withdrawal.destroy');
    Route::get('/qc/withdrawal/export', [WithdrawalController::class, 'export'])->name('qc.withdrawal.export');
});
