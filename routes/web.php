<?php

use App\Http\Controllers\Admin\AdminDocumentController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\VendorController;
use App\Http\Controllers\Auditor\AuditorDashboardController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
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
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    /*
    |----------------------------------------------------------------
    | Admin routes - Super Admin + Compliance Admin
    |----------------------------------------------------------------
    */
    Route::middleware('role:super_admin,compliance_admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

            // Vendor resource routes
            Route::resource('vendors', VendorController::class)->except(['destroy']);

            // Lifecycle actions
            Route::post('/vendors/{vendor}/invite',          [VendorController::class, 'invite'])->name('vendors.invite');
            Route::get('/vendors/{vendor}/invite',           [VendorController::class, 'inviteForm'])->name('vendors.invite-form');
            Route::post('/vendors/{vendor}/suspend',         [VendorController::class, 'suspend'])->name('vendors.suspend');
            Route::post('/vendors/{vendor}/reinstate',       [VendorController::class, 'reinstate'])->name('vendors.reinstate');
            Route::post('/vendors/{vendor}/archive',         [VendorController::class, 'archive'])->name('vendors.archive');
            Route::post('/vendors/{vendor}/assign-reviewer', [VendorController::class, 'assignReviewer'])->name('vendors.assign-reviewer');

            // Admin can upload documents on behalf of a vendor
            Route::post('/vendors/{vendor}/documents/upload', [AdminDocumentController::class, 'store'])->name('vendors.documents.store');

            // Reports (HTML pages)
            Route::prefix('reports')->name('reports.')->group(function () {
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
        });
});
