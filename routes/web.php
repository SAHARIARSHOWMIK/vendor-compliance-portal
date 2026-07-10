<?php

use App\Http\Controllers\Admin\AdminDocumentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auditor\AuditorDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Reviewer\ReviewQueueController;
use App\Http\Controllers\VendorPortal\AcceptInvitationController;
use App\Http\Controllers\VendorPortal\VendorDocumentController;
use App\Http\Controllers\VendorPortal\VendorPortalDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public / guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Single entry point after login - redirects by role.
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');

    /*
    |----------------------------------------------------------------
    | Vendor portfolio - read access for admins and auditors
    |----------------------------------------------------------------
    */
    Route::middleware('role:super_admin,compliance_admin,auditor')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/vendors', [VendorController::class, 'index'])->name('vendors.index');
            Route::get('/vendors/{vendor}', [VendorController::class, 'show'])
                ->whereNumber('vendor')
                ->name('vendors.show');
        });

    /*
    |----------------------------------------------------------------
    | Administrative vendor operations - write access only
    |----------------------------------------------------------------
    */
    Route::middleware('role:super_admin,compliance_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            Route::get('/vendors/create', [VendorController::class, 'create'])->name('vendors.create');
            Route::post('/vendors', [VendorController::class, 'store'])->name('vendors.store');
            Route::get('/vendors/{vendor}/edit', [VendorController::class, 'edit'])
                ->whereNumber('vendor')
                ->name('vendors.edit');
            Route::match(['put', 'patch'], '/vendors/{vendor}', [VendorController::class, 'update'])
                ->whereNumber('vendor')
                ->name('vendors.update');

            Route::post('/vendors/{vendor}/invite', [VendorController::class, 'invite'])
                ->whereNumber('vendor')
                ->name('vendors.invite');
            Route::get('/vendors/{vendor}/invite', [VendorController::class, 'inviteForm'])
                ->whereNumber('vendor')
                ->name('vendors.invite-form');
            Route::post('/vendors/{vendor}/suspend', [VendorController::class, 'suspend'])
                ->whereNumber('vendor')
                ->name('vendors.suspend');
            Route::post('/vendors/{vendor}/reinstate', [VendorController::class, 'reinstate'])
                ->whereNumber('vendor')
                ->name('vendors.reinstate');
            Route::post('/vendors/{vendor}/archive', [VendorController::class, 'archive'])
                ->whereNumber('vendor')
                ->name('vendors.archive');
            Route::post('/vendors/{vendor}/assign-reviewer', [VendorController::class, 'assignReviewer'])
                ->whereNumber('vendor')
                ->name('vendors.assign-reviewer');

            Route::post('/vendors/{vendor}/documents/upload', [AdminDocumentController::class, 'store'])
                ->whereNumber('vendor')
                ->name('vendors.documents.store');
        });

    /*
    |----------------------------------------------------------------
    | Reports - Super Admin + Compliance Admin + read-only Auditor
    |----------------------------------------------------------------
    */
    Route::middleware('role:super_admin,compliance_admin,auditor')
        ->prefix('admin/reports')
        ->name('admin.reports.')
        ->group(function () {
                Route::get('/',                     [ReportController::class, 'index'])->name('index');
                Route::get('/compliance-summary',   [ReportController::class, 'complianceSummary'])->name('compliance-summary');
                Route::get('/missing-documents',    [ReportController::class, 'missingDocuments'])->name('missing-documents');
                Route::get('/expiring-documents',   [ReportController::class, 'expiringDocuments'])->name('expiring-documents');
                Route::get('/rejected-documents',   [ReportController::class, 'rejectedDocuments'])->name('rejected-documents');
                Route::get('/vendor-onboarding',     [ReportController::class, 'vendorOnboarding'])->name('vendor-onboarding');
                Route::get('/reviewer-workload',    [ReportController::class, 'reviewerWorkload'])->name('reviewer-workload');
                Route::get('/audit-log',            [ReportController::class, 'auditLog'])->name('audit-log');

                // CSV exports
                Route::get('/compliance-summary/export', [ReportController::class, 'exportComplianceSummary'])->name('compliance-summary.export');
                Route::get('/missing-documents/export',  [ReportController::class, 'exportMissingDocuments'])->name('missing-documents.export');
                Route::get('/expiring-documents/export', [ReportController::class, 'exportExpiringDocuments'])->name('expiring-documents.export');
                Route::get('/rejected-documents/export', [ReportController::class, 'exportRejectedDocuments'])->name('rejected-documents.export');
                Route::get('/vendor-onboarding/export',  [ReportController::class, 'exportVendorOnboarding'])->name('vendor-onboarding.export');
                Route::get('/reviewer-workload/export',  [ReportController::class, 'exportReviewerWorkload'])->name('reviewer-workload.export');
                Route::get('/audit-log/export',          [ReportController::class, 'exportAuditLog'])->name('audit-log.export');
        });

    /*
    |----------------------------------------------------------------
    | Reviewer routes
    |----------------------------------------------------------------
    */
    Route::middleware('role:super_admin,compliance_admin,reviewer')
        ->prefix('reviewer')
        ->name('reviewer.')
        ->group(function () {
            Route::get('/queue',                          [ReviewQueueController::class, 'index'])->name('queue');
            Route::get('/documents/{document}',           [ReviewQueueController::class, 'show'])->name('documents.show');
            Route::post('/documents/{document}/decide',   [ReviewQueueController::class, 'decide'])->name('documents.decide');
        });

    /*
    |----------------------------------------------------------------
    | Vendor portal routes - VendorUser only, vendor-scoped
    |----------------------------------------------------------------
    */
    Route::middleware(['role:vendor_user', 'vendor.scope'])
        ->prefix('vendor-portal')
        ->name('vendor-portal.')
        ->group(function () {
            Route::get('/dashboard', [VendorPortalDashboardController::class, 'index'])->name('dashboard');
            Route::get('/accept-invitation/{token}',  [AcceptInvitationController::class, 'show'])->name('accept-invitation');
            Route::post('/accept-invitation/{token}', [AcceptInvitationController::class, 'store'])->name('accept-invitation.store');

            // Document checklist and upload
            Route::get('/checklist',                             [VendorDocumentController::class, 'checklist'])->name('checklist');
            Route::get('/vendors/{vendor}/upload',                [VendorDocumentController::class, 'uploadForm'])->name('documents.upload-form');
            Route::post('/vendors/{vendor}/upload',               [VendorDocumentController::class, 'store'])->name('documents.store');
            Route::get('/documents/{document}/download',         [VendorDocumentController::class, 'download'])->name('documents.download');
            Route::get('/document-versions/{version}/download', [VendorDocumentController::class, 'downloadVersion'])->name('documents.version-download');
        });

    /*
    |----------------------------------------------------------------
    | Auditor routes - read-only
    |----------------------------------------------------------------
    */
    Route::middleware('role:auditor,super_admin')
        ->prefix('auditor')
        ->name('auditor.')
        ->group(function () {
            Route::get('/dashboard',             [AuditorDashboardController::class, 'index'])->name('dashboard');
            Route::get('/vendors',               [AuditorDashboardController::class, 'vendors'])->name('vendors');
            Route::get('/vendors/{vendor}',      [AuditorDashboardController::class, 'vendorDetail'])->name('vendors.show');
            Route::get('/audit-log', [ReportController::class, 'auditLog'])->name('audit-log');
            Route::get('/audit-log/export', [ReportController::class, 'exportAuditLog'])->name('audit-log.export');
        });
});
