<?php

use App\Http\Controllers\Admin\ActionPlanController as AdminActionPlanController;
use App\Http\Controllers\Admin\AuditCategoryController as AdminAuditCategoryController;
use App\Http\Controllers\Admin\AuditController as AdminAuditController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FindingController as AdminFindingController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SopController as AdminSopController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auditor\AuditController as AuditorAuditController;
use App\Http\Controllers\Auditor\DashboardController as AuditorDashboardController;
use App\Http\Controllers\Auditor\EvidenceVerificationController;
use App\Http\Controllers\Auditor\FindingController as AuditorFindingController;
use App\Http\Controllers\Auditee\ActionPlanController;
use App\Http\Controllers\Auditee\AuditController as AuditeeAuditController;
use App\Http\Controllers\Auditee\DashboardController as AuditeeDashboardController;
use App\Http\Controllers\Auditee\EvidenceController;
use App\Http\Controllers\Auditee\FindingController as AuditeeFindingController;
use App\Http\Controllers\Coordinator\AuditController as CoordinatorAuditController;
use App\Http\Controllers\Coordinator\DashboardController as CoordinatorDashboardController;
use App\Http\Controllers\Coordinator\FindingController as CoordinatorFindingController;
use App\Http\Controllers\Coordinator\ReportController as CoordinatorReportController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// --- Root redirect ---
Route::get('/', function () {
    if (auth()->check()) {
        return redirect(auth()->user()->getRedirectRoute());
    }

    return redirect()->route('login');
});

// --- Profile (Breeze default) ---
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/admin', fn () => redirect()->route('admin.dashboard'));

// ==========================================
// ADMIN ROUTES (VUE + INERTIA)
// ==========================================
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->name('users.create');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::patch('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');

    // Stores
    Route::get('/stores', [AdminStoreController::class, 'index'])->name('stores.index');
    Route::get('/stores/create', [AdminStoreController::class, 'create'])->name('stores.create');
    Route::post('/stores', [AdminStoreController::class, 'store'])->name('stores.store');
    Route::get('/stores/{store}/edit', [AdminStoreController::class, 'edit'])->name('stores.edit');
    Route::put('/stores/{store}', [AdminStoreController::class, 'update'])->name('stores.update');
    Route::delete('/stores/{store}', [AdminStoreController::class, 'destroy'])->name('stores.destroy');

    // Audit Categories
    Route::get('/audit-categories', [AdminAuditCategoryController::class, 'index'])->name('audit-categories.index');
    Route::post('/audit-categories', [AdminAuditCategoryController::class, 'store'])->name('audit-categories.store');
    Route::put('/audit-categories/{auditCategory}', [AdminAuditCategoryController::class, 'update'])->name('audit-categories.update');
    Route::delete('/audit-categories/{auditCategory}', [AdminAuditCategoryController::class, 'destroy'])->name('audit-categories.destroy');

    // SOP / SE
    Route::get('/sops', [AdminSopController::class, 'index'])->name('sops.index');
    Route::post('/sops', [AdminSopController::class, 'store'])->name('sops.store');
    Route::put('/sops/{sop}', [AdminSopController::class, 'update'])->name('sops.update');
    Route::delete('/sops/{sop}', [AdminSopController::class, 'destroy'])->name('sops.destroy');

    // Audits
    Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/create', [AdminAuditController::class, 'create'])->name('audits.create');
    Route::post('/audits', [AdminAuditController::class, 'store'])->name('audits.store');
    Route::get('/audits/{audit}', [AdminAuditController::class, 'show'])->name('audits.show');
    Route::get('/audits/{audit}/edit', [AdminAuditController::class, 'edit'])->name('audits.edit');
    Route::put('/audits/{audit}', [AdminAuditController::class, 'update'])->name('audits.update');
    Route::delete('/audits/{audit}', [AdminAuditController::class, 'destroy'])->name('audits.destroy');
    Route::post('/audits/{audit}/findings', [AdminAuditController::class, 'storeFinding'])->name('audits.findings.store');

    // Findings
    Route::get('/findings', [AdminFindingController::class, 'index'])->name('findings.index');
    Route::get('/findings/{finding}', [AdminFindingController::class, 'show'])->name('findings.show');
    Route::patch('/findings/{finding}/review-severity', [AdminFindingController::class, 'reviewSeverity'])->name('findings.review-severity');
    Route::delete('/findings/{finding}', [AdminFindingController::class, 'destroy'])->name('findings.destroy');

    // Action Plans
    Route::get('/action-plans', [AdminActionPlanController::class, 'index'])->name('action-plans.index');
    Route::patch('/action-plans/{actionPlan}', [AdminActionPlanController::class, 'update'])->name('action-plans.update');

    // Reports
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-findings', [AdminReportController::class, 'exportFindings'])->name('reports.export-findings');
    Route::get('/reports/export-stores', [AdminReportController::class, 'exportStores'])->name('reports.export-stores');
    Route::get('/reports/export-summary', [AdminReportController::class, 'exportSummary'])->name('reports.export-summary');
});

// ==========================================
// COORDINATOR ROUTES (KOORDINATOR AUDIT)
// ==========================================
Route::middleware(['auth', 'role:coordinator'])->prefix('coordinator')->name('coordinator.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [CoordinatorDashboardController::class, 'index'])->name('dashboard');

    // Severity Reviews / Findings
    Route::get('/findings', [CoordinatorFindingController::class, 'index'])->name('findings.index');
    Route::get('/findings/{finding}', [CoordinatorFindingController::class, 'show'])->name('findings.show');
    Route::patch('/findings/{finding}/review-severity', [CoordinatorFindingController::class, 'reviewSeverity'])->name('findings.review-severity');

    // Audits Monitoring
    Route::get('/audits', [CoordinatorAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [CoordinatorAuditController::class, 'show'])->name('audits.show');

    // Reports
    Route::get('/reports', [CoordinatorReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-findings', [CoordinatorReportController::class, 'exportFindings'])->name('reports.export-findings');
    Route::get('/reports/export-stores', [CoordinatorReportController::class, 'exportStores'])->name('reports.export-stores');
    Route::get('/reports/export-summary', [CoordinatorReportController::class, 'exportSummary'])->name('reports.export-summary');
});

// ==========================================
// AUDITOR ROUTES
// ==========================================
Route::middleware(['auth', 'role:auditor'])->prefix('auditor')->name('auditor.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AuditorDashboardController::class, 'index'])->name('dashboard');

    // Audits
    Route::get('/audits', [AuditorAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditorAuditController::class, 'show'])->name('audits.show');

    // Findings
    Route::get('/audits/{audit}/findings/create', [AuditorFindingController::class, 'create'])->name('findings.create');
    Route::post('/audits/{audit}/findings', [AuditorFindingController::class, 'store'])->name('findings.store');
    Route::get('/findings/{finding}', [AuditorFindingController::class, 'show'])->name('findings.show');
    Route::get('/findings/{finding}/edit', [AuditorFindingController::class, 'edit'])->name('findings.edit');
    Route::patch('/findings/{finding}', [AuditorFindingController::class, 'update'])->name('findings.update');
    Route::patch('/findings/{finding}/close', [AuditorFindingController::class, 'close'])->name('findings.close');

    // Evidence Verification
    Route::get('/verification', [EvidenceVerificationController::class, 'index'])->name('verification.index');
    Route::patch('/evidences/{evidence}/approve', [EvidenceVerificationController::class, 'approve'])->name('evidences.approve');
    Route::patch('/evidences/{evidence}/reject', [EvidenceVerificationController::class, 'reject'])->name('evidences.reject');
});

// ==========================================
// AUDITEE ROUTES
// ==========================================
Route::middleware(['auth', 'role:auditee'])->prefix('auditee')->name('auditee.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AuditeeDashboardController::class, 'index'])->name('dashboard');

    // Audits
    Route::get('/audits', [AuditeeAuditController::class, 'index'])->name('audits.index');
    Route::get('/audits/{audit}', [AuditeeAuditController::class, 'show'])->name('audits.show');

    // Findings
    Route::get('/findings/{finding}', [AuditeeFindingController::class, 'show'])->name('findings.show');

    // Action Plans
    Route::post('/findings/{finding}/action-plan', [ActionPlanController::class, 'store'])->name('action-plans.store');
    Route::patch('/findings/{finding}/action-plan', [ActionPlanController::class, 'update'])->name('action-plans.update');

    // Evidence Upload
    Route::post('/findings/{finding}/evidences', [EvidenceController::class, 'store'])->name('evidences.store');
    Route::delete('/evidences/{evidence}', [EvidenceController::class, 'destroy'])->name('evidences.destroy');
});

require __DIR__ . '/auth.php';
