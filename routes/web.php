<?php

use App\Http\Controllers\Applications\InsuranceApplicationController;
use App\Http\Controllers\Applications\LoanApplicationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Documents\DocumentController;
use App\Http\Controllers\Finance\DisbursementController;
use App\Http\Controllers\Finance\InvoiceController;
use App\Http\Controllers\Finance\PaymentController;
use App\Http\Controllers\Leads\LeadController;
use App\Http\Controllers\Master\MasterController;
use App\Http\Controllers\Products\LenderController;
use App\Http\Controllers\Products\ProductController;
use App\Http\Controllers\System\AttendanceController;
use App\Http\Controllers\System\EmployeeController;
use App\Http\Controllers\System\GlobalSearchController;
use App\Http\Controllers\System\NotificationController;
use App\Http\Controllers\System\ProfileController;
use App\Http\Controllers\System\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/', [LoginController::class, 'showLogin'])->name('login');
    Route::get('login', [LoginController::class, 'showLogin'])->name('login.form');
    Route::post('login', [LoginController::class, 'login'])->name('login.attempt')->middleware('throttle:10,1');
});

Route::post('logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Authenticated application
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'active'])->group(function () {

    /* Dashboard ---------------------------------------------------------- */
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/kpis', [DashboardController::class, 'kpis'])->name('dashboard.kpis');
    Route::get('my-workspace', [DashboardController::class, 'myWorkspace'])->name('dashboard.workspace');

    /* Profile / account -------------------------------------------------- */
    Route::get('profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::get('profile/notifications', [ProfileController::class, 'notifications'])->name('profile.notifications');
    Route::get('change-password', [LoginController::class, 'showChangePassword'])->name('password.change');
    Route::post('change-password', [LoginController::class, 'changePassword'])->name('password.update');

    /* Global search ------------------------------------------------------ */
    Route::get('search', GlobalSearchController::class)->name('search');

    /* Product master ----------------------------------------------------- */
    Route::get('products', [ProductController::class, 'index'])->name('products.index');
    Route::get('products/categories', [ProductController::class, 'categories'])->name('products.categories');
    Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');

    Route::get('lenders', [LenderController::class, 'index'])->name('lenders.index');
    Route::get('lenders/products', [LenderController::class, 'products'])->name('lenders.products');
    Route::get('lenders/{lender}', [LenderController::class, 'show'])->name('lenders.show');

    /* Lead management ---------------------------------------------------- */
    Route::prefix('leads')->name('leads.')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('index');
        Route::get('export', [LeadController::class, 'export'])->name('export');
        Route::get('drafts', [LeadController::class, 'draftResume'])->name('drafts');
        Route::get('create', [LeadController::class, 'create'])->name('create');
        Route::post('/', [LeadController::class, 'store'])->name('store');

        Route::get('{lead}', [LeadController::class, 'show'])->name('show');
        Route::get('{lead}/wizard/{step?}', [LeadController::class, 'wizard'])->name('wizard');
        Route::post('{lead}/wizard/{step}', [LeadController::class, 'saveStep'])->name('wizard.save');
        Route::post('{lead}/otp', [LeadController::class, 'sendOtp'])->name('otp.send');
        Route::post('{lead}/otp/verify', [LeadController::class, 'verifyOtp'])->name('otp.verify');
        Route::post('{lead}/status', [LeadController::class, 'changeStatus'])->name('status');
        Route::post('{lead}/assign', [LeadController::class, 'assign'])->name('assign');
        Route::post('{lead}/remarks', [LeadController::class, 'addRemark'])->name('remarks.store');
        Route::post('{lead}/convert', [LeadController::class, 'convert'])->name('convert');
        Route::get('{lead}/quick-view', [LeadController::class, 'quickView'])->name('quick-view');
        Route::delete('{lead}', [LeadController::class, 'destroy'])->name('destroy');
    });

    /* Customers ---------------------------------------------------------- */
    Route::resource('customers', CustomerController::class)->except(['show']);
    Route::get('customers/export', [CustomerController::class, 'export'])->name('customers.export');
    Route::get('customers/{customer}/timeline', [CustomerController::class, 'timeline'])->name('customers.timeline');
    Route::get('customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');

    /* Loan applications -------------------------------------------------- */
    Route::prefix('applications')->name('applications.')->group(function () {
        Route::get('/', [LoanApplicationController::class, 'index'])->name('index');
        Route::get('export', [LoanApplicationController::class, 'export'])->name('export');
        Route::get('create', [LoanApplicationController::class, 'create'])->name('create');
        Route::get('{application}/quick-view', [LoanApplicationController::class, 'quickView'])->name('quick-view');
        Route::get('{application}', [LoanApplicationController::class, 'show'])->name('show');
        Route::post('{application}/status', [LoanApplicationController::class, 'changeStatus'])->name('status');
        Route::post('{application}/remarks', [LoanApplicationController::class, 'addRemark'])->name('remarks.store');
        Route::post('{application}/assign', [LoanApplicationController::class, 'assign'])->name('assign');
    });

    /* Insurance applications --------------------------------------------- */
    Route::prefix('insurance')->name('insurance.')->group(function () {
        Route::get('/', [LoanApplicationController::class, 'insurance'])->name('index');
        Route::get('create', [InsuranceApplicationController::class, 'create'])->name('create');
        Route::post('/', [InsuranceApplicationController::class, 'store'])->name('store');
        Route::get('{insurance}', [LoanApplicationController::class, 'showInsurance'])->name('show');
        Route::put('{insurance}', [InsuranceApplicationController::class, 'update'])->name('update');
    });

    /* Disbursements ------------------------------------------------------ */
    Route::prefix('disbursements')->name('disbursements.')->group(function () {
        Route::get('/', [DisbursementController::class, 'index'])->name('index');
        Route::get('export', [DisbursementController::class, 'export'])->name('export');
        Route::get('create', [DisbursementController::class, 'create'])->name('create');
        Route::post('/', [DisbursementController::class, 'store'])->name('store');
        Route::get('{disbursement}/edit', [DisbursementController::class, 'edit'])->name('edit');
        Route::put('{disbursement}', [DisbursementController::class, 'update'])->name('update');
        Route::post('{disbursement}/status', [DisbursementController::class, 'changeStatus'])->name('status');
        Route::get('{disbursement}', [DisbursementController::class, 'show'])->name('show');
    });

    /* Invoices ----------------------------------------------------------- */
    Route::prefix('invoices')->name('invoices.')->group(function () {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('export', [InvoiceController::class, 'export'])->name('export');
        Route::get('create', [InvoiceController::class, 'create'])->name('create');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('{invoice}/edit', [InvoiceController::class, 'edit'])->name('edit');
        Route::put('{invoice}', [InvoiceController::class, 'update'])->name('update');
        Route::post('{invoice}/issue', [InvoiceController::class, 'issue'])->name('issue');
        Route::post('{invoice}/status', [InvoiceController::class, 'changeStatus'])->name('status');
        Route::get('{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('pdf');
        Route::get('{invoice}/print', [InvoiceController::class, 'print'])->name('print');
        Route::get('{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::delete('{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
    });

    /* Payments ----------------------------------------------------------- */
    Route::prefix('payments')->name('payments.')->group(function () {
        Route::get('/', [PaymentController::class, 'index'])->name('index');
        Route::get('export', [PaymentController::class, 'export'])->name('export');
        Route::get('create', [PaymentController::class, 'create'])->name('create');
        Route::post('/', [PaymentController::class, 'store'])->name('store');
        Route::get('{payment}/edit', [PaymentController::class, 'edit'])->name('edit');
        Route::put('{payment}', [PaymentController::class, 'update'])->name('update');
        Route::get('{payment}', [PaymentController::class, 'show'])->name('show');
        Route::delete('{payment}', [PaymentController::class, 'destroy'])->name('destroy');
    });

    /* Documents ---------------------------------------------------------- */
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::post('/', [DocumentController::class, 'store'])->name('store');
        Route::get('{document}/preview', [DocumentController::class, 'preview'])->name('preview');
        Route::get('{document}/download', [DocumentController::class, 'download'])->name('download');
        Route::post('{document}/replace', [DocumentController::class, 'replace'])->name('replace');
        Route::post('{document}/verify', [DocumentController::class, 'verify'])->name('verify');
        Route::post('{document}/reject', [DocumentController::class, 'reject'])->name('reject');
        Route::delete('{document}', [DocumentController::class, 'destroy'])->name('destroy');
    });

    /* Notifications ------------------------------------------------------ */
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('feed', [NotificationController::class, 'feed'])->name('feed');
        Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
        Route::post('{notification}/read', [NotificationController::class, 'markRead'])->name('read');
        Route::delete('{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });

    /* Attendance --------------------------------------------------------- */
    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::get('export', [AttendanceController::class, 'export'])->name('export');
        Route::post('check-in', [AttendanceController::class, 'checkIn'])->name('check-in');
        Route::post('check-out', [AttendanceController::class, 'checkOut'])->name('check-out');
        Route::post('mark', [AttendanceController::class, 'mark'])->name('mark');
        Route::post('leave', [AttendanceController::class, 'requestLeave'])->name('leave.request');
        Route::post('leave/{leave}/decide', [AttendanceController::class, 'decideLeave'])->name('leave.decide');
    });

    /* Reports ------------------------------------------------------------ */
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('{report}/export', [ReportController::class, 'export'])->name('export');
        Route::get('{report}/pdf', [ReportController::class, 'pdf'])->name('pdf');
        Route::get('{report}', [ReportController::class, 'show'])->name('show');
    });

    /* Employees ---------------------------------------------------------- */
    Route::prefix('employees')->name('employees.')->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])->name('index');
        Route::get('export', [EmployeeController::class, 'export'])->name('export');
        Route::get('create', [EmployeeController::class, 'create'])->name('create');
        Route::post('/', [EmployeeController::class, 'store'])->name('store');
        Route::get('{employee}/edit', [EmployeeController::class, 'edit'])->name('edit');
        Route::put('{employee}', [EmployeeController::class, 'update'])->name('update');
        Route::post('{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('{employee}', [EmployeeController::class, 'show'])->name('show');
    });

    /* Master management -------------------------------------------------- */
    Route::prefix('masters')->name('masters.')->group(function () {
        Route::get('/', [MasterController::class, 'index'])->name('index');
        Route::get('{master}/options', [MasterController::class, 'options'])->name('options');
        Route::get('{master}', [MasterController::class, 'show'])->name('show');
        Route::post('{master}', [MasterController::class, 'store'])->name('store');
        Route::put('{master}/{id}', [MasterController::class, 'update'])->name('update');
        Route::post('{master}/{id}/toggle', [MasterController::class, 'toggle'])->name('toggle');
        Route::delete('{master}/{id}', [MasterController::class, 'destroy'])->name('destroy');
    });

    /* Settings ----------------------------------------------------------- */
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [\App\Http\Controllers\System\SettingController::class, 'index'])->name('index');
        Route::put('/', [\App\Http\Controllers\System\SettingController::class, 'update'])->name('update');
    });

    /* Audit log ---------------------------------------------------------- */
    Route::get('audit-log', [\App\Http\Controllers\System\SettingController::class, 'auditLog'])->name('audit.index');
});
