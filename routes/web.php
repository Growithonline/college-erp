<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Institute\Auth\LoginController;
use App\Http\Controllers\Institute\DashboardController;
use App\Http\Controllers\Institute\Master\AcademicSessionController;
use App\Http\Controllers\Institute\Master\SessionViewSwitchController;
use App\Http\Controllers\Institute\Master\CourseController;
use App\Http\Controllers\Institute\Master\CourseStreamController;
use App\Http\Controllers\Institute\Master\CourseSubjectController;
use App\Http\Controllers\Institute\Master\SubjectController;
use App\Http\Controllers\Institute\Master\FeeTypeController;
use App\Http\Controllers\Institute\Master\FeeAssignmentController;
use App\Http\Controllers\Institute\Master\StudentTypeController;
use App\Http\Controllers\Institute\Master\CourseTypeController;
use App\Http\Controllers\Institute\Master\CourseFeeRuleController;
use App\Http\Controllers\Institute\Master\SubjectFeeRuleController;
use App\Http\Controllers\Institute\Master\FeePlanController;
use App\Http\Controllers\Institute\Master\CenterController;
use App\Http\Controllers\Institute\Master\AdmissionFormController;
use App\Http\Controllers\Institute\Master\ChannelPartnerController;
use App\Http\Controllers\Institute\Master\StaffRoleController;
use App\Http\Controllers\Institute\Master\StaffMemberController;
use App\Http\Controllers\Institute\Admission\AdmissionController;
use App\Http\Controllers\Institute\Admission\EnquiryController;
use App\Http\Controllers\Institute\Admission\StudentBulkImportController;
use App\Http\Controllers\Institute\Admission\StudentPromoteController;
use App\Http\Controllers\Institute\Admission\PromotionController;
use App\Http\Controllers\Institute\Fee\FeeCollectionController;
use App\Http\Controllers\Institute\Fee\FeeApprovalController;
use App\Http\Controllers\Institute\Fee\PracticalFeeTokenController;
use App\Http\Controllers\Institute\Fee\WalletController;
use App\Http\Controllers\Institute\Fee\FeeWalletController;
use App\Http\Controllers\Institute\Reports\ReportController;
use App\Http\Controllers\Institute\Reports\FeeLedgerReportController;
use App\Http\Controllers\Institute\StatementController;
use App\Http\Controllers\Institute\Master\LoginPasswordController;
use App\Http\Controllers\Institute\Master\BankAccountController;
use App\Http\Controllers\Institute\Finance\ExpenseController;
use App\Http\Controllers\Institute\Finance\FinanceSettingController;
use App\Http\Controllers\Institute\Finance\FinanceReportController;
use App\Http\Controllers\Institute\Finance\SalaryController;
use App\Http\Controllers\Institute\Finance\Wallet\WalletDashboardController;
use App\Http\Controllers\Institute\Finance\Wallet\IncomeCategoryController;
use App\Http\Controllers\Institute\Finance\Wallet\ManualIncomeController;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseCategoryL1Controller;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseCategoryL2Controller;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseVendorController;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseApprovalController;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseApprovalLimitController;
use App\Http\Controllers\Institute\Finance\Wallet\ChequePaymentController;
use App\Http\Controllers\Institute\Finance\Wallet\ContraEntryController;
use App\Http\Controllers\Institute\Finance\Wallet\ExpenseCategoryAjaxController;
use App\Http\Controllers\Institute\Payroll\AttendanceController;
use App\Http\Controllers\Institute\Payroll\PayrollController;
use App\Http\Controllers\Institute\Payroll\StaffLoanController;
use App\Http\Controllers\Institute\Payroll\StudentAttendanceController;
use App\Http\Controllers\Institute\StudentDirectoryController;
use App\Http\Controllers\Institute\Library\LibraryDashboardController;
use App\Http\Controllers\Institute\Library\LibraryCategoryController;
use App\Http\Controllers\Institute\Library\LibraryAuthorController;
use App\Http\Controllers\Institute\Library\LibraryPublisherController;
use App\Http\Controllers\Institute\Library\LibraryRackController;
use App\Http\Controllers\Institute\Library\LibrarySubjectController;
use App\Http\Controllers\Institute\Library\LibraryVendorController;
use App\Http\Controllers\Institute\Library\LibraryRuleSetController;
use App\Http\Controllers\Institute\Library\LibraryBookController;
use App\Http\Controllers\Institute\Library\LibraryMemberController;
use App\Http\Controllers\Institute\Library\LibraryCirculationController;
use App\Http\Controllers\Institute\Library\LibraryReportController;
use App\Http\Controllers\Institute\Library\LibraryReservationController;
use App\Http\Controllers\Institute\Library\LibraryFineCollectionController;
use App\Http\Controllers\Institute\Library\LibraryNoDueController;
use App\Http\Controllers\Institute\Certificate\CertificateSettingController;
use App\Http\Controllers\Institute\Certificate\CertificateTypeController;
use App\Http\Controllers\Institute\Certificate\CertificateController;
use App\Http\Controllers\Institute\Master\DocumentCategoryController;
use App\Http\Controllers\Institute\Master\DocumentTypeController;
use App\Http\Controllers\Institute\Master\DocumentRuleController;
use App\Http\Controllers\Institute\Master\SmsSettingController as InstituteSmsSettingController;
use App\Http\Controllers\Institute\Master\SmsDueReminderController;
use App\Http\Controllers\Institute\Admission\AdmissionDocumentController;
use App\Http\Controllers\Institute\Transport\TransportAllocationController;
use App\Http\Controllers\Institute\Transport\TransportDashboardController;
use App\Http\Controllers\Institute\Transport\TransportDriverController;
use App\Http\Controllers\Institute\Transport\TransportMaintenanceController;
use App\Http\Controllers\Institute\Transport\TransportComplianceController;
use App\Http\Controllers\Institute\Transport\TransportBillingController;
use App\Http\Controllers\Institute\Transport\TransportReportController;
use App\Http\Controllers\Institute\Transport\TransportRouteController;
use App\Http\Controllers\Institute\Transport\TransportRouteAssignmentController;
use App\Http\Controllers\Institute\Transport\TransportVehicleController;
use App\Http\Controllers\Institute\Transport\TransportVehicleTypeController;
use App\Http\Controllers\Institute\Transport\TransportSettingController;
use App\Http\Controllers\Institute\Employee\EmployeeController;
use App\Http\Controllers\Institute\Employee\EmployeeDepartmentController;
use App\Http\Controllers\Institute\Employee\EmployeeDesignationController;
use App\Http\Controllers\Institute\Employee\EmployeeSalaryController;

// Library Staff
use App\Http\Controllers\Institute\Library\LibraryStaffController;
use App\Http\Controllers\LibraryStaff\LibraryStaffAuthController;
use App\Http\Controllers\Student\StudentAuthController;

// Phase 7 — Role Login Controllers
use App\Http\Controllers\Center\CenterAuthController;
use App\Http\Controllers\Center\CenterStudentController;
use App\Http\Controllers\Center\CenterFeeController;
use App\Http\Controllers\Center\CenterReportController;
use App\Http\Controllers\Staff\StaffAuthController;
use App\Http\Controllers\Staff\StaffAdmissionController;
use App\Http\Controllers\Staff\StaffFeeController;
use App\Http\Controllers\Staff\StaffFinanceController;
use App\Http\Controllers\Staff\StaffPayrollController;
use App\Http\Controllers\Partner\PartnerAuthController;
use App\Http\Controllers\Partner\PartnerStudentController;
use App\Http\Controllers\Partner\PartnerFeeController;
use App\Http\Controllers\Partner\PartnerReportController;

// ── Public receipt viewer (QR code scan — no auth required) ────────
Route::get('/', fn() => view('welcome'))->name('welcome');

// ── Session expired / account disabled — shown on any portal ─────────
Route::get('/session-expired', function (\Illuminate\Http\Request $request) {
    return view('auth.session-expired', [
        'guard'  => $request->query('guard', 'web'),
        'reason' => $request->query('reason', 'unauthenticated'),
    ]);
})->name('session.expired')->middleware('throttle:60,1');

Route::get('/receipt/balance', [\App\Http\Controllers\PublicReceiptController::class, 'balance'])->name('receipt.balance')->middleware('throttle:30,1');
Route::get('/receipt/record',  [\App\Http\Controllers\PublicReceiptController::class, 'record'])->name('receipt.record')->middleware('throttle:30,1');

// ── Public transport pass viewer (QR code scan — no auth required) ────
Route::get('/transport/pass-status', [\App\Http\Controllers\TransportPassController::class, 'status'])->name('transport.pass.status')->middleware('throttle:30,1');

// ── Public admission enquiry form (per-institute, no auth required) ───
Route::get ('/apply/{shortName}',           [\App\Http\Controllers\Public\AdmissionEnquiryController::class, 'show'])->name('public.admission.show')->middleware('throttle:30,1');
Route::post('/apply/{shortName}/send-otp',  [\App\Http\Controllers\Public\AdmissionEnquiryController::class, 'sendOtp'])->name('public.admission.send-otp')->middleware('throttle:5,1');
Route::post('/apply/{shortName}/verify-otp',[\App\Http\Controllers\Public\AdmissionEnquiryController::class, 'verifyOtp'])->name('public.admission.verify-otp')->middleware('throttle:10,1');
Route::post('/apply/{shortName}',           [\App\Http\Controllers\Public\AdmissionEnquiryController::class, 'store'])->name('public.admission.store')->middleware('throttle:10,1');

Route::get('/login',       [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login',      [LoginController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
Route::get('/otp-verify',  [LoginController::class, 'showOtpForm'])->name('otp.form');
Route::post('/otp-verify', [LoginController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
Route::post('/otp-resend', [LoginController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');
Route::post('/logout',     [LoginController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('institute.dashboard');

    // MASTER
    Route::prefix('master')->name('master.')->group(function () {

        Route::resource('sessions', AcademicSessionController::class);
        Route::post('sessions/{session}/activate', [AcademicSessionController::class, 'activate'])
             ->name('sessions.activate');
        Route::post('sessions/view-switch', [SessionViewSwitchController::class, 'switch'])
             ->name('sessions.view-switch');

        Route::resource('courses', CourseController::class);
        Route::post('courses/{course}/toggle-status', [CourseController::class, 'toggleStatus'])
             ->name('courses.toggle-status');
        Route::resource('courses.streams', CourseStreamController::class)->shallow();
        Route::post('streams/set-limit', [CourseStreamController::class, 'setLimit'])->name('streams.set-limit');
        Route::get('streams/{stream}/year-rules',  [CourseStreamController::class, 'yearRules'])
             ->name('streams.year-rules');
        Route::post('streams/{stream}/year-rules', [CourseStreamController::class, 'saveYearRules'])
             ->name('streams.year-rules.save');

        Route::get('streams/subjects/for-admission',
            [CourseSubjectController::class, 'getSubjectsForAdmission']
        )->name('streams.subjects.for-admission')->middleware('throttle:60,1');
        Route::get('streams/{stream}/subjects',        [CourseSubjectController::class, 'index'])->name('streams.subjects.index');
        Route::post('streams/{stream}/subjects',       [CourseSubjectController::class, 'store'])->name('streams.subjects.store')->middleware('throttle:30,1');
        Route::post('streams/{stream}/subjects/bulk',  [CourseSubjectController::class, 'bulkStore'])->name('streams.subjects.bulk-store')->middleware('throttle:10,1');
        Route::patch('streams/{stream}/subjects/{mapping}/toggle', [CourseSubjectController::class, 'toggle'])->name('streams.subjects.toggle')->middleware('throttle:30,1');
        Route::patch('streams/{stream}/subjects/{mapping}',        [CourseSubjectController::class, 'update'])->name('streams.subjects.update')->middleware('throttle:30,1');
        Route::delete('streams/{stream}/subjects/{mapping}',       [CourseSubjectController::class, 'destroy'])->name('streams.subjects.destroy')->middleware('throttle:30,1');

        Route::resource('subjects', SubjectController::class);
        Route::post('subjects/{subject}/toggle-status', [SubjectController::class, 'toggleStatus'])
             ->name('subjects.toggle-status');

        Route::resource('course-types', CourseTypeController::class)->except(['create', 'edit', 'show']);
        Route::post('course-types/{courseType}/toggle', [CourseTypeController::class, 'toggle'])->name('course-types.toggle');

        Route::resource('student-types', StudentTypeController::class)->except(['create', 'edit', 'show']);
        Route::post('student-types/{studentType}/toggle', [StudentTypeController::class, 'toggle'])->name('student-types.toggle');
        Route::post('student-types/reorder', [StudentTypeController::class, 'reorder'])->name('student-types.reorder');

        Route::resource('fee-types', FeeTypeController::class);
        Route::post('fee-types/{feeType}/toggle', [FeeTypeController::class, 'toggle'])
             ->name('fee-types.toggle');
        Route::resource('fee-assignments', FeeAssignmentController::class);

        // Fee Plans
        Route::get('fee-plans',                              [FeePlanController::class, 'index'])->name('fee-plans.index');
        Route::post('fee-plans',                             [FeePlanController::class, 'store'])->middleware('throttle:20,1')->name('fee-plans.store');
        Route::get('fee-plans/for-course',                   [FeePlanController::class, 'forCourse'])->middleware('throttle:60,1')->name('fee-plans.for-course');
        Route::get('fee-plans/report',                        [FeePlanController::class, 'report'])->name('fee-plans.report');
        Route::patch('fee-plans/{feePlan}',                  [FeePlanController::class, 'update'])->name('fee-plans.update');
        Route::patch('fee-plans/{feePlan}/toggle',           [FeePlanController::class, 'toggleStatus'])->name('fee-plans.toggle');
        Route::delete('fee-plans/{feePlan}',                 [FeePlanController::class, 'destroy'])->name('fee-plans.destroy');

        Route::get('fee-structure/course-fees',                    [CourseFeeRuleController::class, 'index'])->name('fee-structure.course-fees');
        Route::post('fee-structure/course-fees',                   [CourseFeeRuleController::class, 'store'])->name('fee-structure.course-fees.store');
        Route::patch('fee-structure/course-fees/{courseFeeRule}',  [CourseFeeRuleController::class, 'update'])->name('fee-structure.course-fees.update');
        Route::delete('fee-structure/course-fees/{courseFeeRule}', [CourseFeeRuleController::class, 'destroy'])->name('fee-structure.course-fees.destroy');

        Route::get('fee-structure/subject-fees/summary',  [SubjectFeeRuleController::class, 'summary'])->name('fee-structure.subject-fees.summary');
        Route::get('fee-structure/subject-fees/get-fees', [SubjectFeeRuleController::class, 'getSubjectFees'])->name('fee-structure.subject-fees.get');
        Route::get('fee-structure/subject-fees',                      [SubjectFeeRuleController::class, 'index'])->name('fee-structure.subject-fees');
        Route::post('fee-structure/subject-fees',                     [SubjectFeeRuleController::class, 'store'])->name('fee-structure.subject-fees.store');
        Route::post('fee-structure/subject-fees/bulk',                [SubjectFeeRuleController::class, 'bulkStore'])->name('fee-structure.subject-fees.bulk');
        Route::patch('fee-structure/subject-fees/{subjectFeeRule}',   [SubjectFeeRuleController::class, 'update'])->name('fee-structure.subject-fees.update');
        Route::delete('fee-structure/subject-fees/{subjectFeeRule}',  [SubjectFeeRuleController::class, 'destroy'])->name('fee-structure.subject-fees.destroy');

        Route::resource('centers', CenterController::class);
        Route::post('centers/{center}/toggle',          [CenterController::class, 'toggle'])->name('centers.toggle');
        Route::get('centers/archived/list',             [CenterController::class, 'trashed'])->name('centers.trashed');
        Route::post('centers/{id}/restore',             [CenterController::class, 'restore'])->name('centers.restore');
        Route::delete('centers/{id}/force-delete',      [CenterController::class, 'forceDelete'])->name('centers.force-delete');

        Route::resource('channel-partners', ChannelPartnerController::class);
        Route::post('channel-partners/{channelPartner}/toggle',      [ChannelPartnerController::class, 'toggle'])->name('channel-partners.toggle');
        Route::get('channel-partners/archived/list',                 [ChannelPartnerController::class, 'trashed'])->name('channel-partners.trashed');
        Route::post('channel-partners/{id}/restore',                 [ChannelPartnerController::class, 'restore'])->name('channel-partners.restore');
        Route::delete('channel-partners/{id}/force-delete',          [ChannelPartnerController::class, 'forceDelete'])->name('channel-partners.force-delete');

        Route::resource('staff-roles', StaffRoleController::class);
        Route::resource('staff-members', StaffMemberController::class);
        Route::post('staff-members/{staffMember}/toggle',            [StaffMemberController::class, 'toggle'])->name('staff-members.toggle');
        Route::get('staff-members/archived/list',                    [StaffMemberController::class, 'trashed'])->name('staff-members.trashed');
        Route::post('staff-members/{id}/restore',                    [StaffMemberController::class, 'restore'])->name('staff-members.restore');
        Route::delete('staff-members/{id}/force-delete',             [StaffMemberController::class, 'forceDelete'])->name('staff-members.force-delete');

        // Phase 8: Bank Accounts & Payment Permissions
        Route::get('bank-accounts',                        [BankAccountController::class, 'index'])->name('bank-accounts.index');
        Route::get('bank-accounts/create',                 [BankAccountController::class, 'create'])->name('bank-accounts.create');
        Route::post('bank-accounts',                       [BankAccountController::class, 'store'])->name('bank-accounts.store');
        Route::get('bank-accounts/permissions',            [BankAccountController::class, 'permissions'])->name('bank-accounts.permissions');
        Route::post('bank-accounts/permissions',           [BankAccountController::class, 'savePermissions'])->name('bank-accounts.permissions.save');
        Route::get('bank-accounts/{bankAccount}/edit',     [BankAccountController::class, 'edit'])->name('bank-accounts.edit');
        Route::patch('bank-accounts/{bankAccount}',        [BankAccountController::class, 'update'])->name('bank-accounts.update');
        Route::patch('bank-accounts/{bankAccount}/toggle', [BankAccountController::class, 'toggle'])->name('bank-accounts.toggle');
        Route::delete('bank-accounts/{bankAccount}',       [BankAccountController::class, 'destroy'])->name('bank-accounts.destroy');

        Route::get('forms',         [AdmissionFormController::class, 'index'])->name('forms.index');
        Route::get('forms/{type}',  [AdmissionFormController::class, 'builder'])->name('forms.builder')
             ->where('type', 'admission|quick|online|receipt');
        Route::post('forms/{type}', [AdmissionFormController::class, 'save'])->name('forms.save')
             ->where('type', 'admission|quick|online|receipt');

        // Document Masters
        Route::get('document-categories',                                  [DocumentCategoryController::class, 'index'])->name('document-categories.index');
        Route::post('document-categories',                                 [DocumentCategoryController::class, 'store'])->name('document-categories.store');
        Route::put('document-categories/{documentCategory}',              [DocumentCategoryController::class, 'update'])->name('document-categories.update');
        Route::post('document-categories/{documentCategory}/toggle',       [DocumentCategoryController::class, 'toggle'])->name('document-categories.toggle');
        Route::delete('document-categories/{documentCategory}',            [DocumentCategoryController::class, 'destroy'])->name('document-categories.destroy');

        Route::get('document-types',                               [DocumentTypeController::class, 'index'])->name('document-types.index');
        Route::post('document-types',                              [DocumentTypeController::class, 'store'])->name('document-types.store');
        Route::put('document-types/{documentType}',                [DocumentTypeController::class, 'update'])->name('document-types.update');
        Route::post('document-types/{documentType}/toggle',        [DocumentTypeController::class, 'toggle'])->name('document-types.toggle');
        Route::delete('document-types/{documentType}',             [DocumentTypeController::class, 'destroy'])->name('document-types.destroy');

        // Document Upload Rules (Course × User Type matrix)
        Route::get('document-rules',                       [DocumentRuleController::class, 'index'])->name('document-rules.index');
        // Static routes MUST come before {course} dynamic route
        Route::get('document-rules/notification-settings', [DocumentRuleController::class, 'notificationSettings'])->name('document-rules.notification-settings');
        Route::post('document-rules/notification-settings',[DocumentRuleController::class, 'saveNotificationSettings'])->name('document-rules.notification-settings.save');
        Route::get('document-rules/{course}',              [DocumentRuleController::class, 'show'])->name('document-rules.show');
        Route::post('document-rules/{course}',             [DocumentRuleController::class, 'save'])->name('document-rules.save');

        // SMS Settings
        Route::get('sms',                   [InstituteSmsSettingController::class, 'index'])->name('sms.index');
        Route::post('sms/save',             [InstituteSmsSettingController::class, 'save'])->name('sms.save');
        Route::post('sms/test-connection',  [InstituteSmsSettingController::class, 'testConnection'])->name('sms.test-connection');
        Route::get('sms/balance',           [InstituteSmsSettingController::class, 'checkBalance'])->name('sms.check-balance');
        Route::get('sms/logs',              [InstituteSmsSettingController::class, 'logs'])->name('sms.logs');

        // SMS Due Reminders
        Route::get('sms/reminders',         [SmsDueReminderController::class, 'index'])->name('sms.reminders.index');
        Route::post('sms/reminders/save',   [SmsDueReminderController::class, 'save'])->name('sms.reminders.save');
        Route::post('sms/reminders/toggle', [SmsDueReminderController::class, 'toggle'])->name('sms.reminders.toggle');

        // Email (SMTP) Settings
        Route::get('settings/email',             [\App\Http\Controllers\Institute\Settings\SmtpSettingController::class, 'index'])->name('settings.email');
        Route::post('settings/email/save',       [\App\Http\Controllers\Institute\Settings\SmtpSettingController::class, 'save'])->name('settings.email.save');
        Route::post('settings/email/test',       [\App\Http\Controllers\Institute\Settings\SmtpSettingController::class, 'testConnection'])->name('settings.email.test');
        Route::post('settings/email/disconnect', [\App\Http\Controllers\Institute\Settings\SmtpSettingController::class, 'disconnect'])->name('settings.email.disconnect');

        // Data Export / Backup
        Route::get('settings/data-export', [\App\Http\Controllers\Institute\Settings\DataExportController::class, 'download'])->name('settings.data-export');
        Route::get('settings/backup',                    [\App\Http\Controllers\Institute\Settings\BackupController::class, 'index'])->name('settings.backup');
        Route::get('settings/backup/students-excel',     [\App\Http\Controllers\Institute\Settings\BackupController::class, 'downloadStudentExcel'])->name('settings.backup.students');
        Route::get('settings/backup/financial-excel',    [\App\Http\Controllers\Institute\Settings\BackupController::class, 'downloadFinancialExcel'])->name('settings.backup.financial');
    });

    // Library Staff management — institute admin only (CRUD, reports)
    Route::prefix('library/staff')->name('library.staff.')->group(function () {
        Route::get('/',                           [LibraryStaffController::class, 'index'])->name('index');
        Route::get('/create',                     [LibraryStaffController::class, 'create'])->name('create');
        Route::post('/',                          [LibraryStaffController::class, 'store'])->name('store');
        Route::get('/login-logs',                 [LibraryStaffController::class, 'loginLogs'])->name('login-logs');
        Route::get('/activity-logs',              [LibraryStaffController::class, 'activityLogs'])->name('activity-logs');
        Route::get('/{libraryStaff}/edit',        [LibraryStaffController::class, 'edit'])->name('edit');
        Route::put('/{libraryStaff}',             [LibraryStaffController::class, 'update'])->name('update');
        Route::post('/{libraryStaff}/toggle',     [LibraryStaffController::class, 'toggle'])->name('toggle');
        Route::post('/{libraryStaff}/resend-credentials', [LibraryStaffController::class, 'resendCredentials'])->name('resend-credentials');
        Route::post('/{libraryStaff}/reset-lock', [LibraryStaffController::class, 'resetLock'])->name('reset-lock');
        Route::delete('/{libraryStaff}',          [LibraryStaffController::class, 'destroy'])->name('destroy');
    });

    // FEE COLLECTION
    Route::prefix('fee')->name('fee.')->group(function () {
        Route::get('/',                            [FeeCollectionController::class, 'index'])->name('index');
        Route::get('/collect',                     [FeeCollectionController::class, 'create'])->name('create');
        Route::post('/collect',                    [FeeCollectionController::class, 'store'])->name('store');
        Route::get('/search-student',              [FeeCollectionController::class, 'searchStudent'])->name('search-student');
        Route::get('/export',                      [FeeCollectionController::class, 'export'])->name('export');
        Route::get('/{student}/history',           [FeeCollectionController::class, 'studentHistory'])->name('student-history');
        Route::get('/{student}/receipt/{invoice}', [FeeCollectionController::class, 'receipt'])->name('receipt');
        Route::get('/{student}/wallet',            [WalletController::class, 'studentWallet'])->name('wallet.student');
        Route::post('/{student}/invoice/{invoice}/cancel', [FeeCollectionController::class, 'cancel'])->name('cancel');
        Route::get('/practical-tokens', [PracticalFeeTokenController::class, 'index'])->name('practical-tokens.index');
        Route::get('/practical-tokens/create', [PracticalFeeTokenController::class, 'create'])->name('practical-tokens.create');
        Route::post('/practical-tokens', [PracticalFeeTokenController::class, 'store'])->name('practical-tokens.store');
        Route::get('/practical-tokens/{batch}', [PracticalFeeTokenController::class, 'show'])->name('practical-tokens.show');
        Route::post('/practical-tokens/{batch}/entries', [PracticalFeeTokenController::class, 'postEntries'])->name('practical-tokens.entries.store');
    });

    // FEE WALLETS (token-based collection control for centers & channel partners)
    Route::prefix('fee-wallets')->name('fee-wallets.')->group(function () {
        Route::get('centers',                                    [FeeWalletController::class, 'centerIndex'])->name('centers');
        Route::get('centers/{center}/create',                    [FeeWalletController::class, 'createCenter'])->name('center.create');
        Route::post('centers/{center}',                          [FeeWalletController::class, 'storeCenter'])->name('center.store');
        Route::post('center-wallet/{wallet}/extend',             [FeeWalletController::class, 'centerExtend'])->name('center.extend');
        Route::post('center-wallet/{wallet}/toggle',             [FeeWalletController::class, 'centerToggle'])->name('center.toggle');
        Route::get('center-wallet/{wallet}/transactions',        [FeeWalletController::class, 'centerTransactions'])->name('center.transactions');

        Route::get('channels',                                   [FeeWalletController::class, 'channelIndex'])->name('channels');
        Route::get('channels/{channelPartner}/create',           [FeeWalletController::class, 'createChannel'])->name('channel.create');
        Route::post('channels/{channelPartner}',                 [FeeWalletController::class, 'storeChannel'])->name('channel.store');
        Route::post('channel-wallet/{wallet}/extend',            [FeeWalletController::class, 'channelExtend'])->name('channel.extend');
        Route::post('channel-wallet/{wallet}/toggle',            [FeeWalletController::class, 'channelToggle'])->name('channel.toggle');
        Route::get('channel-wallet/{wallet}/transactions',       [FeeWalletController::class, 'channelTransactions'])->name('channel.transactions');

        Route::get('extension-requests',                         [FeeWalletController::class, 'extensionRequests'])->name('extension-requests');
        Route::post('extension-requests/{extensionRequest}/approve', [FeeWalletController::class, 'approveRequest'])->name('extension-requests.approve');
        Route::post('extension-requests/{extensionRequest}/reject',  [FeeWalletController::class, 'rejectRequest'])->name('extension-requests.reject');
    });

    Route::prefix('finance')->name('finance.')->middleware('finance.access')->group(function () {
        Route::get('/settings', [FinanceSettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [FinanceSettingController::class, 'update'])->name('settings.update');
        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/reverse', [ExpenseController::class, 'reverseForm'])->name('expenses.reverse');
        Route::post('/expenses/{expense}/reverse', [ExpenseController::class, 'reverse'])->name('expenses.reverse.store');
        Route::get('/salary', [SalaryController::class, 'index'])->name('salary.index');
        Route::get('/salary/create', [SalaryController::class, 'create'])->name('salary.create');
        Route::post('/salary', [SalaryController::class, 'store'])->name('salary.store');
        Route::get('/salary/{salaryRecord}/pay', [SalaryController::class, 'pay'])->name('salary.pay');
        Route::post('/salary/{salaryRecord}/pay', [SalaryController::class, 'markPaid'])->name('salary.mark-paid');
        Route::get('/salary/{salaryRecord}/reverse', [SalaryController::class, 'reverseForm'])->name('salary.reverse');
        Route::post('/salary/{salaryRecord}/reverse', [SalaryController::class, 'reverse'])->name('salary.reverse.store');

        // ── Payroll: Attendance & Salary Management ──────────────────────────
        Route::prefix('payroll')->name('payroll.')->group(function () {
            // Attendance
            Route::get('/attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
            Route::post('/attendance/mark', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::post('/attendance/bulk-mark', [AttendanceController::class, 'bulkMark'])->name('attendance.bulk-mark');
            Route::get('/attendance/monthly', [AttendanceController::class, 'monthly'])->name('attendance.monthly');
            Route::post('/attendance/lock-month', [AttendanceController::class, 'lockMonth'])->name('attendance.lock-month');
            Route::post('/attendance/unlock-month', [AttendanceController::class, 'unlockMonth'])->name('attendance.unlock-month');

            // Payroll
            Route::post('/generate-draft', [PayrollController::class, 'generateDraft'])->name('generate-draft');
            Route::get('/draft', [PayrollController::class, 'draftView'])->name('draft-view');
            Route::post('/approve/{salaryRecord}', [PayrollController::class, 'approveSalary'])->name('approve');
            Route::post('/mark-paid/{salaryRecord}', [PayrollController::class, 'markPaid'])->name('mark-paid');
            Route::post('/bulk-pay', [PayrollController::class, 'bulkPay'])->name('bulk-pay');
            Route::post('/reverse/{salaryRecord}', [PayrollController::class, 'reverse'])->name('reverse');
            Route::get('/summary', [PayrollController::class, 'summary'])->name('summary');
            Route::get('/payslip/{salaryRecord}', [PayrollController::class, 'payslip'])->name('payslip');

            // Staff Loans & Advances
            Route::prefix('loans')->name('loans.')->group(function () {
                Route::get('/', [StaffLoanController::class, 'index'])->name('index');
                Route::post('/', [StaffLoanController::class, 'store'])->name('store');
                Route::patch('/{staffLoan}/cancel', [StaffLoanController::class, 'cancel'])->name('cancel');
            });

            // Student Attendance
            Route::prefix('student-attendance')->name('student-attendance.')->group(function () {
                Route::get('/daily', [StudentAttendanceController::class, 'daily'])->name('daily');
                Route::post('/mark', [StudentAttendanceController::class, 'store'])->name('store');
                Route::post('/bulk-mark', [StudentAttendanceController::class, 'bulkMark'])->name('bulk-mark');
                Route::get('/monthly', [StudentAttendanceController::class, 'monthly'])->name('monthly');
            });
        });

        Route::get('/reports/ledger', [FinanceReportController::class, 'ledger'])->name('reports.ledger');
        Route::get('/reports/day-book', [FinanceReportController::class, 'dayBook'])->name('reports.day-book');
        Route::get('/reports/cash-book', [FinanceReportController::class, 'cashBook'])->name('reports.cash-book');
        Route::get('/reports/bank-book', [FinanceReportController::class, 'bankBook'])->name('reports.bank-book');
        Route::get('/reports/trial-balance', [FinanceReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('/reports/profit-loss', [FinanceReportController::class, 'profitAndLoss'])->name('reports.profit-loss');
        Route::get('/reports/reconciliation', [FinanceReportController::class, 'reconciliation'])->name('reports.reconciliation');
        Route::get('/reports/income-book', [FinanceReportController::class, 'incomeBook'])->name('reports.income-book');

        // ── Institute Wallet (Phase 1-6) ──────────────────────────────────────
        Route::prefix('wallet')->name('wallet.')->group(function () {
            Route::get('/', [WalletDashboardController::class, 'index'])->name('dashboard');
            Route::get('/ledger', [WalletDashboardController::class, 'ledger'])->name('ledger');
            Route::get('/ledger/expense-category', [WalletDashboardController::class, 'expenseCategoryLedger'])->name('expense-category-ledger');

            // Phase 4: Cheque tracking
            Route::get('cheques', [ChequePaymentController::class, 'index'])->name('cheques.index');
            Route::patch('cheques/{cheque}/status', [ChequePaymentController::class, 'updateStatus'])->name('cheques.update-status');
            Route::post('cheques/manual', [ChequePaymentController::class, 'addManual'])->name('cheques.add-manual');

            // Phase 5: Contra entries
            Route::get('contra', [ContraEntryController::class, 'index'])->name('contra.index');
            Route::post('contra', [ContraEntryController::class, 'store'])->name('contra.store');
            Route::delete('contra/{contraEntry}', [ContraEntryController::class, 'destroy'])->name('contra.destroy');

            // Income categories (manual)
            Route::resource('income-categories', IncomeCategoryController::class)->except(['show']);

            // Manual income entries
            Route::get('manual-income', [ManualIncomeController::class, 'index'])->name('manual-income.index');
            Route::get('manual-income/create', [ManualIncomeController::class, 'create'])->name('manual-income.create');
            Route::post('manual-income', [ManualIncomeController::class, 'store'])->name('manual-income.store');

            // Expense categories L1
            Route::resource('expense-categories', ExpenseCategoryL1Controller::class)->except(['show'])
                ->parameter('expense-categories', 'expenseCategory');

            // Expense categories L2 (nested under L1)
            Route::prefix('expense-categories/{expenseCategory}/sub')->name('expense-categories.sub.')->group(function () {
                Route::get('/', [ExpenseCategoryL2Controller::class, 'index'])->name('index');
                Route::get('/create', [ExpenseCategoryL2Controller::class, 'create'])->name('create');
                Route::post('/', [ExpenseCategoryL2Controller::class, 'store'])->name('store');
                Route::get('/{sub}/edit', [ExpenseCategoryL2Controller::class, 'edit'])->name('edit');
                Route::put('/{sub}', [ExpenseCategoryL2Controller::class, 'update'])->name('update');
                Route::delete('/{sub}', [ExpenseCategoryL2Controller::class, 'destroy'])->name('destroy');

                // Vendors (nested under L2)
                Route::prefix('/{sub}/vendors')->name('vendors.')->group(function () {
                    Route::get('/', [ExpenseVendorController::class, 'index'])->name('index');
                    Route::get('/create', [ExpenseVendorController::class, 'create'])->name('create');
                    Route::post('/', [ExpenseVendorController::class, 'store'])->name('store');
                    Route::get('/{vendor}/edit', [ExpenseVendorController::class, 'edit'])->name('edit');
                    Route::put('/{vendor}', [ExpenseVendorController::class, 'update'])->name('update');
                    Route::delete('/{vendor}', [ExpenseVendorController::class, 'destroy'])->name('destroy');
                });
            });

            // Phase 6: Expense Approval Workflow
            Route::get('expense-approvals', [ExpenseApprovalController::class, 'index'])->name('expense-approvals.index');
            Route::post('expense-approvals/{expense}/approve', [ExpenseApprovalController::class, 'approve'])->name('expense-approvals.approve');
            Route::post('expense-approvals/{expense}/reject', [ExpenseApprovalController::class, 'reject'])->name('expense-approvals.reject');

            // Phase 6: Approval limits per role
            Route::get('approval-limits', [ExpenseApprovalLimitController::class, 'index'])->name('approval-limits.index');
            Route::post('approval-limits', [ExpenseApprovalLimitController::class, 'update'])->name('approval-limits.update');

            // AJAX: cascade dropdowns
            Route::get('ajax/sub-categories', [ExpenseCategoryAjaxController::class, 'subCategories'])->name('ajax.sub-categories');
            Route::get('ajax/vendors', [ExpenseCategoryAjaxController::class, 'vendors'])->name('ajax.vendors');

            // Phase 7: Reports
            Route::get('reports/income', [WalletDashboardController::class, 'incomeReport'])->name('reports.income');
            Route::get('reports/expense', [WalletDashboardController::class, 'expenseReport'])->name('reports.expense');
            Route::get('reports/session-comparison', [WalletDashboardController::class, 'sessionComparison'])->name('reports.session-comparison');

            // Phase 7: Low balance threshold update
            Route::post('threshold', [WalletDashboardController::class, 'updateThreshold'])->name('threshold.update');
        });
    });

    // ── Phase 7: Set login passwords for center/staff/partner ───────────
    Route::patch('master/center/{center}/set-password',  [LoginPasswordController::class, 'setCenterPassword'])->name('master.center.set-password');
    Route::patch('master/staff/{staff}/set-password',    [LoginPasswordController::class, 'setStaffPassword'])->name('master.staff.set-password');
    Route::patch('master/partner/{partner}/set-password',[LoginPasswordController::class, 'setPartnerPassword'])->name('master.partner.set-password');

    // STATEMENT — Get Student Balance + Fee Record
    Route::prefix('statement')->name('statement.')->group(function () {
        Route::get('/search-student',  [StatementController::class, 'searchStudent'])->name('search-student');
        Route::get('/balance',         [StatementController::class, 'studentBalance'])->name('balance');
        Route::get('/fee-record',      [StatementController::class, 'feeRecord'])->name('fee-record');
        Route::get('/export-csv',      [StatementController::class, 'exportCsv'])->name('export-csv');
    });

    // NOTICES
    Route::prefix('notices')->name('notices.')->group(function () {
        Route::get('/',                    [\App\Http\Controllers\Institute\NoticeController::class, 'index'])->name('index');
        Route::get('/create',              [\App\Http\Controllers\Institute\NoticeController::class, 'create'])->name('create');
        Route::post('/',                   [\App\Http\Controllers\Institute\NoticeController::class, 'store'])->name('store');
        Route::get('/{notice}/edit',       [\App\Http\Controllers\Institute\NoticeController::class, 'edit'])->name('edit');
        Route::put('/{notice}',            [\App\Http\Controllers\Institute\NoticeController::class, 'update'])->name('update');
        Route::delete('/{notice}',         [\App\Http\Controllers\Institute\NoticeController::class, 'destroy'])->name('destroy');
        Route::patch('/{notice}/toggle',   [\App\Http\Controllers\Institute\NoticeController::class, 'toggle'])->name('toggle');
        Route::patch('/{notice}/pin',      [\App\Http\Controllers\Institute\NoticeController::class, 'pin'])->name('pin');
        Route::post('/{notice}/read',      [\App\Http\Controllers\Institute\NoticeController::class, 'markRead'])->name('read');
        Route::get('/{notice}/reads',      [\App\Http\Controllers\Institute\NoticeController::class, 'readDetail'])->name('reads');
    });

    // STUDENT DIRECTORY
    Route::prefix('students')->name('students.')->group(function () {
        Route::get('/',             [StudentDirectoryController::class, 'index'])->name('index');
        Route::get('/quick',        [StudentDirectoryController::class, 'quickAdmissions'])->name('quick');
        Route::get('/export',       [StudentDirectoryController::class, 'export'])->name('export');
        Route::get('/search',       [StudentDirectoryController::class, 'search'])->name('search');
        Route::get('/wallet',       [StudentDirectoryController::class, 'wallet'])->name('wallet');
        Route::get('/history',      [StudentDirectoryController::class, 'feeHistory'])->name('history');
        Route::get('/ajax-search',  [StudentDirectoryController::class, 'ajaxSearch'])->name('ajax-search');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/fee-due-list',        [ReportController::class, 'feeDueList'])->name('fee-due-list');
        Route::get('/fee-collection',      [ReportController::class, 'feeCollectionReport'])->name('fee-collection');
        Route::get('/cancelled-fee',       [ReportController::class, 'cancelledFeeReport'])->name('cancelled-fee');
        Route::get('/admission',           [ReportController::class, 'admissionReport'])->name('admission');
        Route::get('/daily-collection',    [ReportController::class, 'dailyReport'])->name('daily-collection');
        Route::get('/semester-wise',       [ReportController::class, 'semesterReport'])->name('semester-wise');
        Route::get('/custom-student',      [ReportController::class, 'customStudentReport'])->name('custom-student');
        Route::get('/streams',             [ReportController::class, 'getStreams'])->name('streams');

        // Admission sub-reports
        Route::get('/admission/full-form',           [ReportController::class, 'fullFormAdmissionReport'])->name('admission.full-form');
        Route::get('/admission/online',              [ReportController::class, 'onlineAdmissionReport'])->name('admission.online');
        Route::get('/admission/centre',              [ReportController::class, 'centreAdmissionReport'])->name('admission.centre');
        Route::get('/admission/channel-partner',     [ReportController::class, 'channelPartnerAdmissionReport'])->name('admission.channel-partner');
        Route::get('/admission/staff',               [ReportController::class, 'staffAdmissionReport'])->name('admission.staff');
        Route::get('/admission/blocked',             [ReportController::class, 'blockedStudentsReport'])->name('admission.blocked');

        // Fee Ledger — Bulk Course-wise
        Route::get('/fee-ledger',              [FeeLedgerReportController::class, 'index'])->name('fee-ledger.index');
        Route::get('/fee-ledger/print',        [FeeLedgerReportController::class, 'printAll'])->name('fee-ledger.print');
        Route::get('/fee-ledger/export-csv',   [FeeLedgerReportController::class, 'exportCsv'])->name('fee-ledger.export-csv');
        Route::get('/fee-ledger/export-excel', [FeeLedgerReportController::class, 'exportExcel'])->name('fee-ledger.export-excel');
        Route::post('/fee-ledger/queue-pdf',   [FeeLedgerReportController::class, 'queuePdf'])->name('fee-ledger.queue-pdf');
        Route::get('/fee-ledger/pdf-status',   [FeeLedgerReportController::class, 'pdfStatus'])->name('fee-ledger.pdf-status');
        Route::get('/fee-ledger/download-pdf', [FeeLedgerReportController::class, 'downloadPdf'])->name('fee-ledger.download-pdf');

        // Fee collection sub-reports
        Route::get('/fee-collection/staff',                    [ReportController::class, 'staffCollectionReport'])->name('fee-collection.staff');
        Route::get('/fee-collection/staff/{staffId}',          [ReportController::class, 'staffCollectionDetail'])->name('fee-collection.staff.detail');
        Route::get('/fee-collection/centre',                   [ReportController::class, 'centreCollectionReport'])->name('fee-collection.centre');
        Route::get('/fee-collection/centre/{centreId}',        [ReportController::class, 'centreCollectionDetail'])->name('fee-collection.centre.detail');
        Route::get('/fee-collection/channel-partner',          [ReportController::class, 'channelPartnerCollectionReport'])->name('fee-collection.channel-partner');
        Route::get('/fee-collection/channel-partner/{partnerId}', [ReportController::class, 'channelPartnerCollectionDetail'])->name('fee-collection.channel-partner.detail');
        Route::get('/fee-collection/practical-token',          [ReportController::class, 'practicalTokenCollectionReport'])->name('fee-collection.practical-token');
    });

    // ADMISSIONS — static routes PEHLE, resource LAST

    Route::get('admissions/online',        [AdmissionController::class, 'onlineAdmissions'])->name('admissions.online');
    Route::get('admissions/quick',         [AdmissionController::class, 'quickCreate'])->name('admissions.quick-create');
    Route::post('admissions/quick',        [AdmissionController::class, 'quickStore'])->name('admissions.quick-store');
    Route::post('admissions/quick-confirm', [AdmissionController::class, 'quickConfirm'])->name('admissions.quick-confirm');
    Route::get('admissions/quick-edit',     [AdmissionController::class, 'quickEditPreview'])->name('admissions.quick-edit-preview');

    Route::get('admissions/stream-subjects', [AdmissionController::class, 'getStreamSubjects'])->name('admissions.stream-subjects');
    Route::get('admissions/stream-seats',    [AdmissionController::class, 'getStreamSeats'])->name('admissions.stream-seats');
    Route::get('admissions/course-parts',    [PromotionController::class, 'getCourseParts'])->name('admissions.course-parts');
    Route::post('admissions/fee-preview',    [AdmissionController::class, 'feePreview'])->name('admissions.fee-preview');

    Route::get('admissions/edit-preview',  [AdmissionController::class, 'editPreview'])->name('admissions.edit-preview');
    Route::post('admissions/preview', [AdmissionController::class, 'storePreview'])->name('admissions.preview');
    Route::post('admissions/confirm', [AdmissionController::class, 'confirmStore'])->name('admissions.confirm');
    Route::get('admissions/bulk-correction', [PromotionController::class, 'bulkCorrectionIndex'])->name('admissions.bulk-correction');
    Route::get('admissions/bulk-correction/template', [PromotionController::class, 'bulkCorrectionTemplate'])->name('admissions.bulk-correction.template');
    Route::post('admissions/bulk-correction/upload', [PromotionController::class, 'bulkCorrectionUpload'])->name('admissions.bulk-correction.upload');
    Route::post('admissions/bulk-correction/apply', [PromotionController::class, 'bulkCorrectionApply'])->name('admissions.bulk-correction.apply');

    Route::get('admissions/promote/parts',    [StudentPromoteController::class, 'getParts'])->name('admissions.promote.parts');
    Route::post('admissions/promote/preview', [StudentPromoteController::class, 'preview'])->name('admissions.promote.preview');
    Route::post('admissions/promote',         [StudentPromoteController::class, 'promote'])->name('admissions.promote');
    Route::post('admissions/promote/bulk',    [StudentPromoteController::class, 'bulkPromote'])->name('admissions.promote.bulk');
    Route::get('admissions/promotions',       [PromotionController::class, 'semesterIndex'])->name('admissions.promotions');
    Route::get('admissions/approvals',        [AdmissionController::class, 'approvals'])->name('admissions.approvals.index');
    Route::get('admissions/approvals/{student}', [AdmissionController::class, 'approvalShow'])->name('admissions.approvals.show');
    Route::post('admissions/approvals/{student}/approve', [AdmissionController::class, 'approveAdmission'])->name('admissions.approvals.approve');
    Route::post('admissions/approvals/{student}/status', [AdmissionController::class, 'updateApprovalStatus'])->name('admissions.approvals.status');

    // ── New Promotion Routes ──────────────────────────────────────────
    Route::prefix('admissions/promote')->name('admissions.promote.')->group(function () {
        Route::get ('semester',               [PromotionController::class, 'semesterIndex'])   ->name('semester');
        Route::post('semester',               [PromotionController::class, 'semesterPromote']) ->name('semester.do');
        Route::get ('session',                [PromotionController::class, 'sessionIndex'])    ->name('session');
        Route::post('session',                [PromotionController::class, 'sessionPromote'])  ->name('session.do');
        Route::get ('report',                 [PromotionController::class, 'report'])          ->name('report');
        Route::get ('outcomes',               [PromotionController::class, 'outcomesIndex'])   ->name('outcomes');
        Route::get ('promoted-students',      [PromotionController::class, 'promotedStudents'])->name('promoted-students');
        Route::post('check-status',           [PromotionController::class, 'checkStudentStatus'])->name('check-status');
        // Point 8: Reversal
        Route::post('reverse/{log}',          [PromotionController::class, 'reversePromotion'])->name('reverse');
        // Point 7: Roll No / Form No Identity
        Route::get ('identity',               [PromotionController::class, 'identityIndex'])   ->name('identity');
        Route::get ('identity/template',      [PromotionController::class, 'identityTemplate'])->name('identity.template');
        Route::post('identity/{identity}',    [PromotionController::class, 'identityUpdate'])  ->name('identity.update');
        Route::post('identity-bulk',          [PromotionController::class, 'identityBulkUpdate'])->name('identity.bulk');
        // Short-term course completion (modular/certificate courses)
        Route::post('mark-complete/{student}', [PromotionController::class, 'markComplete'])->name('mark-complete');
        // Re-admission: reinstate a terminal student
        Route::get ('readmit/{student}',       [PromotionController::class, 'readmitForm'])->name('readmit');
        Route::post('readmit/{student}',       [PromotionController::class, 'readmit'])    ->name('readmit.do');
    });

    // {student} param routes
    Route::get('admissions/{student}/success',            [AdmissionController::class, 'quickSuccess'])->name('admissions.quick-success');
    Route::get('admissions/{student}/upload-documents',   [AdmissionDocumentController::class, 'uploadPage'])->name('admissions.upload-documents');
    Route::get('admissions/{student}/receipt/{receipt}',  [AdmissionController::class, 'receiptPrint'])->name('admissions.receipt-print');
    Route::get('admissions/{student}/print-form',         [AdmissionController::class, 'printForm'])->name('admissions.print-form');
    Route::get('admissions/{student}/fee-payment',        [AdmissionController::class, 'feePayment'])->name('admissions.fee-payment');
    Route::get('admissions/{student}/skip-fee-payment',   [AdmissionController::class, 'skipFeePayment'])->name('admissions.skip-fee-payment');
    Route::get('admissions/{student}/print-all',          [AdmissionController::class, 'printAll'])->name('admissions.print-all');
    Route::get('admissions/{student}/print-all/{invoice}',[AdmissionController::class, 'printAll'])->name('admissions.print-all-receipt');
    Route::get('admissions/{student}/edit',               [AdmissionController::class, 'edit'])->name('admissions.edit');
    Route::patch('admissions/{student}',                  [AdmissionController::class, 'update'])->name('admissions.update');

    // Bulk Student Import
    Route::prefix('admissions/bulk-import')->name('admissions.bulk-import.')->middleware('throttle:20,1')->group(function () {
        Route::get('/',          [StudentBulkImportController::class, 'index'])->name('index');
        Route::get('/template',  [StudentBulkImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/preview',  [StudentBulkImportController::class, 'preview'])->name('preview');
        Route::post('/import',   [StudentBulkImportController::class, 'import'])->name('import');
    });

    // Resource LAST
    // Route::resource('admissions', AdmissionController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('admissions', [AdmissionController::class, 'index'])->name('admissions.index');
    Route::get('admissions/create', [AdmissionController::class, 'create'])->name('admissions.create');
    Route::post('admissions', [AdmissionController::class, 'store'])->name('admissions.store');
    Route::get('admissions/{student}', [AdmissionController::class, 'show'])->name('admissions.show');
    Route::post('admissions/{student}/resend-credentials', [AdmissionController::class, 'resendCredentials'])->name('admissions.resend-credentials');

    // Admission Documents (shared across all guards)
    Route::prefix('admission-documents')->name('admission.documents.')->group(function () {
        Route::post('upload/{student}',               [AdmissionDocumentController::class, 'upload'])->name('upload');
        Route::post('{document}/verify',              [AdmissionDocumentController::class, 'verify'])->name('verify');
        Route::post('{document}/reject',              [AdmissionDocumentController::class, 'reject'])->name('reject');
        Route::delete('{document}',                   [AdmissionDocumentController::class, 'destroy'])->name('destroy');
        Route::get('{document}',                      [AdmissionDocumentController::class, 'show'])->name('show');
        Route::get('for-course',                      [AdmissionDocumentController::class, 'getForCourse'])->name('for-course');
    });

    // Online Enquiries
    Route::prefix('enquiries')->name('enquiries.')->group(function () {
        Route::get('/',                    [EnquiryController::class, 'index'])->name('index');
        Route::get('{id}',                 [EnquiryController::class, 'show'])->name('show');
        Route::post('{id}/status',         [EnquiryController::class, 'updateStatus'])->name('update-status');
        Route::post('{id}/assign',         [EnquiryController::class, 'assign'])->name('assign');
        Route::post('{id}/follow-up',      [EnquiryController::class, 'storeFollowUp'])->name('follow-up.store');
    });

    // Certificates
    Route::prefix('certificate')->name('certificate.')->group(function () {
        Route::get('settings',                          [CertificateSettingController::class, 'index'])->name('settings.index');
        Route::put('settings',                          [CertificateSettingController::class, 'update'])->name('settings.update');
        Route::get('settings/remove-image/{field}',    [CertificateSettingController::class, 'removeImage'])->name('settings.remove-image');

        Route::get('types',                             [CertificateTypeController::class, 'index'])->name('types.index');
        Route::post('types',                            [CertificateTypeController::class, 'store'])->name('types.store');
        Route::put('types/{certificateType}',           [CertificateTypeController::class, 'update'])->name('types.update');
        Route::patch('types/{certificateType}/toggle',  [CertificateTypeController::class, 'toggle'])->name('types.toggle');
        Route::delete('types/{certificateType}',        [CertificateTypeController::class, 'destroy'])->name('types.destroy');
        Route::post('types/seed',                       [CertificateTypeController::class, 'seed'])->name('types.seed');

        Route::get('/',                                 [CertificateController::class, 'index'])->name('index');
        Route::get('issue',                             [CertificateController::class, 'create'])->name('create');
        Route::post('issue',                            [CertificateController::class, 'store'])->name('store');
        Route::post('preview',                          [CertificateController::class, 'preview'])->name('preview');
        Route::get('search-student',                    [CertificateController::class, 'searchStudent'])->name('search-student');
        Route::get('{certificate}',                     [CertificateController::class, 'show'])->name('show');
        Route::get('{certificate}/download',            [CertificateController::class, 'download'])->name('download');
        Route::patch('{certificate}/cancel',            [CertificateController::class, 'cancel'])->name('cancel');
    });

    // Profile
    Route::get('/profile',    [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',  [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password',   [\App\Http\Controllers\Auth\PasswordController::class, 'update'])->name('password.update');
});

require __DIR__.'/super_admin.php';

// ── LIBRARY routes — accessible to both institute admin AND library staff ──
Route::middleware(['lib.dual.auth'])->group(function () {
    Route::prefix('library')->name('library.')->group(function () {
        Route::get('/', [LibraryDashboardController::class, 'index'])->name('dashboard');

        Route::get('categories', [LibraryCategoryController::class, 'index'])->name('categories.index');
        Route::post('categories', [LibraryCategoryController::class, 'store'])->name('categories.store');
        Route::put('categories/{category}', [LibraryCategoryController::class, 'update'])->name('categories.update');
        Route::post('categories/{category}/toggle', [LibraryCategoryController::class, 'toggle'])->name('categories.toggle');
        Route::delete('categories/{category}', [LibraryCategoryController::class, 'destroy'])->name('categories.destroy');

        Route::get('authors', [LibraryAuthorController::class, 'index'])->name('authors.index');
        Route::post('authors', [LibraryAuthorController::class, 'store'])->name('authors.store');
        Route::put('authors/{author}', [LibraryAuthorController::class, 'update'])->name('authors.update');
        Route::post('authors/{author}/toggle', [LibraryAuthorController::class, 'toggle'])->name('authors.toggle');
        Route::delete('authors/{author}', [LibraryAuthorController::class, 'destroy'])->name('authors.destroy');

        Route::get('publishers', [LibraryPublisherController::class, 'index'])->name('publishers.index');
        Route::post('publishers', [LibraryPublisherController::class, 'store'])->name('publishers.store');
        Route::put('publishers/{publisher}', [LibraryPublisherController::class, 'update'])->name('publishers.update');
        Route::post('publishers/{publisher}/toggle', [LibraryPublisherController::class, 'toggle'])->name('publishers.toggle');
        Route::delete('publishers/{publisher}', [LibraryPublisherController::class, 'destroy'])->name('publishers.destroy');

        Route::get('subjects', [LibrarySubjectController::class, 'index'])->name('subjects.index');
        Route::post('subjects', [LibrarySubjectController::class, 'store'])->name('subjects.store');
        Route::put('subjects/{subject}', [LibrarySubjectController::class, 'update'])->name('subjects.update');
        Route::post('subjects/{subject}/toggle', [LibrarySubjectController::class, 'toggle'])->name('subjects.toggle');
        Route::delete('subjects/{subject}', [LibrarySubjectController::class, 'destroy'])->name('subjects.destroy');

        Route::get('vendors', [LibraryVendorController::class, 'index'])->name('vendors.index');
        Route::post('vendors', [LibraryVendorController::class, 'store'])->name('vendors.store');
        Route::put('vendors/{vendor}', [LibraryVendorController::class, 'update'])->name('vendors.update');
        Route::post('vendors/{vendor}/toggle', [LibraryVendorController::class, 'toggle'])->name('vendors.toggle');
        Route::delete('vendors/{vendor}', [LibraryVendorController::class, 'destroy'])->name('vendors.destroy');

        Route::get('racks', [LibraryRackController::class, 'index'])->name('racks.index');
        Route::post('racks', [LibraryRackController::class, 'store'])->name('racks.store');
        Route::put('racks/{rack}', [LibraryRackController::class, 'update'])->name('racks.update');
        Route::post('racks/{rack}/toggle', [LibraryRackController::class, 'toggle'])->name('racks.toggle');
        Route::delete('racks/{rack}', [LibraryRackController::class, 'destroy'])->name('racks.destroy');

        Route::get('rules', [LibraryRuleSetController::class, 'index'])->name('rules.index');
        Route::post('rules', [LibraryRuleSetController::class, 'store'])->name('rules.store');
        Route::put('rules/{rule}', [LibraryRuleSetController::class, 'update'])->name('rules.update');
        Route::post('rules/{rule}/toggle', [LibraryRuleSetController::class, 'toggle'])->name('rules.toggle');
        Route::delete('rules/{rule}', [LibraryRuleSetController::class, 'destroy'])->name('rules.destroy');

        Route::get('books', [LibraryBookController::class, 'index'])->name('books.index');
        Route::get('books/create', [LibraryBookController::class, 'create'])->name('books.create');
        Route::post('books', [LibraryBookController::class, 'store'])->name('books.store');
        Route::get('books/{book}', [LibraryBookController::class, 'show'])->name('books.show');
        Route::get('books/{book}/labels', [LibraryBookController::class, 'labels'])->name('books.labels');
        Route::post('books/{book}/generate-barcodes', [LibraryBookController::class, 'generateBarcodes'])->name('books.generate-barcodes');
        Route::get('books/{book}/edit', [LibraryBookController::class, 'edit'])->name('books.edit');
        Route::put('books/{book}', [LibraryBookController::class, 'update'])->name('books.update');
        Route::post('books/{book}/toggle', [LibraryBookController::class, 'toggle'])->name('books.toggle');
        Route::post('books/{book}/copies', [LibraryBookController::class, 'storeCopy'])->name('books.copies.store');
        Route::put('books/{book}/copies/{copy}', [LibraryBookController::class, 'updateCopy'])->name('books.copies.update');

        Route::get('members', [LibraryMemberController::class, 'index'])->name('members.index');
        Route::post('members/sync-students', [LibraryMemberController::class, 'syncStudents'])->name('members.sync-students');
        Route::post('members/sync-staff', [LibraryMemberController::class, 'syncStaff'])->name('members.sync-staff');
        Route::put('members/{member}', [LibraryMemberController::class, 'update'])->name('members.update');

        Route::get('circulation', [LibraryCirculationController::class, 'index'])->name('circulation.index');
        Route::post('circulation/issue', [LibraryCirculationController::class, 'issue'])->name('circulation.issue');
        Route::post('circulation/{transaction}/renew', [LibraryCirculationController::class, 'renew'])->name('circulation.renew');
        Route::post('circulation/{transaction}/return', [LibraryCirculationController::class, 'return'])->name('circulation.return');
        Route::post('circulation/{transaction}/fine', [LibraryCirculationController::class, 'payFine'])->name('circulation.fine');
        Route::get('circulation/{transaction}/receipt', [LibraryCirculationController::class, 'receipt'])->name('circulation.receipt');

        Route::get('reservations', [LibraryReservationController::class, 'index'])->name('reservations.index');
        Route::post('reservations', [LibraryReservationController::class, 'store'])->name('reservations.store');
        Route::post('reservations/{reservation}/fulfill', [LibraryReservationController::class, 'fulfill'])->name('reservations.fulfill');
        Route::post('reservations/{reservation}/cancel', [LibraryReservationController::class, 'cancel'])->name('reservations.cancel');

        Route::get('reports', [LibraryReportController::class, 'index'])->name('reports.index');
        Route::get('no-due', [LibraryNoDueController::class, 'index'])->name('no-due.index');
        Route::get('no-due/{student}/print', [LibraryNoDueController::class, 'print'])->name('no-due.print');

        Route::get('fines', [LibraryFineCollectionController::class, 'index'])->name('fines.index');
        Route::get('fines/{member}', [LibraryFineCollectionController::class, 'show'])->name('fines.show');
        Route::post('fines/{member}/collect', [LibraryFineCollectionController::class, 'collect'])->name('fines.collect');
        Route::get('fines/{member}/receipt/{receiptNo}', [LibraryFineCollectionController::class, 'receipt'])->name('fines.receipt');
    });
});

// ── TRANSPORT routes — shared across institute owner, staff, center & partner
// logins (unlike the block above, which is institute-owner-only via the bare
// 'web' guard). Staff/center/partner admission forms embed a transport
// allocation widget that calls these same routes via AJAX, so this group
// must accept all four guards. Route names are unchanged (transport.*), so
// no blade view needed to change.
Route::middleware('auth:web,staff,center,partner')->prefix('transport')->name('transport.')->group(function () {
    Route::get('/', [TransportDashboardController::class, 'index'])->name('dashboard');

    Route::resource('vehicles', TransportVehicleController::class)->except(['show']);
    Route::post('vehicles/{vehicle}/toggle', [TransportVehicleController::class, 'toggle'])->name('vehicles.toggle');
    Route::delete('vehicles/{vehicle}/documents/{document}', [TransportVehicleController::class, 'deleteDocument'])->name('vehicles.documents.destroy');

    Route::resource('drivers', TransportDriverController::class)->except(['show']);
    Route::post('drivers/{driver}/toggle', [TransportDriverController::class, 'toggle'])->name('drivers.toggle');
    Route::delete('drivers/{driver}/documents/{document}', [TransportDriverController::class, 'deleteDocument'])->name('drivers.documents.destroy');

    Route::resource('routes', TransportRouteController::class);
    Route::get('routes/{route}/stops', [TransportRouteController::class, 'stops'])->name('routes.stops');
    Route::post('routes/{route}/toggle', [TransportRouteController::class, 'toggle'])->name('routes.toggle');

    // Route Assignments
    Route::get('route-assignments', [TransportRouteAssignmentController::class, 'index'])->name('route-assignments.index');
    Route::post('route-assignments', [TransportRouteAssignmentController::class, 'store'])->name('route-assignments.store');
    Route::put('route-assignments/{routeAssignment}', [TransportRouteAssignmentController::class, 'update'])->name('route-assignments.update');
    Route::delete('route-assignments/{routeAssignment}', [TransportRouteAssignmentController::class, 'destroy'])->name('route-assignments.destroy');
    Route::get('route-assignments/for-route', [TransportRouteAssignmentController::class, 'forRoute'])->name('route-assignments.for-route');

    // Bulk routes BEFORE resource (avoids {allocation} capturing "bulk")
    Route::get('allocations/bulk/create', [TransportAllocationController::class, 'bulkCreate'])->name('allocations.bulk-create');
    Route::post('allocations/bulk', [TransportAllocationController::class, 'bulkStore'])->name('allocations.bulk-store');

    Route::resource('allocations', TransportAllocationController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('allocations/{allocation}/edit', [TransportAllocationController::class, 'edit'])->name('allocations.edit');
    Route::put('allocations/{allocation}', [TransportAllocationController::class, 'update'])->name('allocations.update');
    Route::post('allocations/{allocation}/collect-payment', [TransportAllocationController::class, 'collectPayment'])->name('allocations.collect-payment');
    Route::post('allocations/{allocation}/close', [TransportAllocationController::class, 'close'])->name('allocations.close');
    Route::post('allocations/{allocation}/transfer', [TransportAllocationController::class, 'transfer'])->name('allocations.transfer');
    Route::get('allocations/{allocation}/pdf', [TransportAllocationController::class, 'pdf'])->name('allocations.pdf');
    Route::get('allocations/{allocation}/pass', [TransportAllocationController::class, 'pass'])->name('allocations.pass');
    Route::get('allocations/passes/bulk', [TransportAllocationController::class, 'bulkPass'])->name('allocations.pass.bulk');

    Route::get('maintenance', [TransportMaintenanceController::class, 'index'])->name('maintenance.index');
    Route::get('maintenance/create', [TransportMaintenanceController::class, 'create'])->name('maintenance.create');
    Route::post('maintenance', [TransportMaintenanceController::class, 'store'])->name('maintenance.store');
    Route::delete('maintenance/{maintenance}', [TransportMaintenanceController::class, 'destroy'])->name('maintenance.destroy');

    Route::get('compliance', [TransportComplianceController::class, 'index'])->name('compliance.index');

    // Vehicle Types
    Route::get('vehicle-types', [TransportVehicleTypeController::class, 'index'])->name('vehicle-types.index');
    Route::post('vehicle-types', [TransportVehicleTypeController::class, 'store'])->name('vehicle-types.store');
    Route::put('vehicle-types/{vehicleType}', [TransportVehicleTypeController::class, 'update'])->name('vehicle-types.update');
    Route::patch('vehicle-types/{vehicleType}/toggle', [TransportVehicleTypeController::class, 'toggle'])->name('vehicle-types.toggle');
    Route::delete('vehicle-types/{vehicleType}', [TransportVehicleTypeController::class, 'destroy'])->name('vehicle-types.destroy');

    // Reports
    Route::get('reports', [TransportReportController::class, 'index'])->name('reports.index');
    Route::get('reports/route-students', [TransportReportController::class, 'routeStudents'])->name('reports.route-students');
    Route::get('reports/route-students/export', [TransportReportController::class, 'exportRouteStudents'])->name('reports.route-students.export');
    Route::get('reports/due', [TransportReportController::class, 'due'])->name('reports.due');
    Route::get('reports/due/export', [TransportReportController::class, 'exportDue'])->name('reports.due.export');
    Route::get('reports/collection', [TransportReportController::class, 'collection'])->name('reports.collection');
    Route::get('reports/collection/export', [TransportReportController::class, 'exportCollection'])->name('reports.collection.export');
    Route::get('reports/occupancy', [TransportReportController::class, 'occupancy'])->name('reports.occupancy');

    // Billing
    Route::get('billing', [TransportBillingController::class, 'index'])->name('billing.index');
    Route::post('billing/generate', [TransportBillingController::class, 'generate'])->name('billing.generate');
    Route::post('billing/collect-one-time/{allocation}', [TransportBillingController::class, 'collectOneTime'])->name('billing.collect-one-time');
    Route::get('billing/receipt/{transaction}', [TransportBillingController::class, 'receipt'])->name('billing.receipt');

    // Transport Helpers
    Route::resource('helpers', \App\Http\Controllers\Institute\Transport\TransportHelperController::class)->except(['show', 'create', 'edit']);
    Route::post('helpers/{helper}/toggle', [\App\Http\Controllers\Institute\Transport\TransportHelperController::class, 'toggle'])->name('helpers.toggle');

    // Transport Settings
    Route::get('settings', [TransportSettingController::class, 'index'])->name('settings.index');
    Route::put('settings', [TransportSettingController::class, 'update'])->name('settings.update');
});

// ── EMPLOYEE MODULE ──────────────────────────────────────────────────
Route::middleware('auth:web,staff')->prefix('employees')->name('employees.')->group(function () {
    // Departments
    Route::prefix('departments')->name('departments.')->group(function () {
        Route::get('/',                [EmployeeDepartmentController::class, 'index'])->name('index');
        Route::post('/',               [EmployeeDepartmentController::class, 'store'])->name('store');
        Route::put('/{department}',    [EmployeeDepartmentController::class, 'update'])->name('update');
        Route::post('/{department}/toggle', [EmployeeDepartmentController::class, 'toggle'])->name('toggle');
        Route::delete('/{department}', [EmployeeDepartmentController::class, 'destroy'])->name('destroy');
    });

    // Designations
    Route::prefix('designations')->name('designations.')->group(function () {
        Route::get('/',                   [EmployeeDesignationController::class, 'index'])->name('index');
        Route::post('/',                  [EmployeeDesignationController::class, 'store'])->name('store');
        Route::put('/{designation}',      [EmployeeDesignationController::class, 'update'])->name('update');
        Route::delete('/{designation}',   [EmployeeDesignationController::class, 'destroy'])->name('destroy');
    });

    // Salary sub-routes (BEFORE resource to avoid route model binding conflict)
    Route::post('/{employee}/salary/components',            [EmployeeSalaryController::class, 'storeComponent'])->name('salary.storeComponent');
    Route::delete('/{employee}/salary/components/{component}', [EmployeeSalaryController::class, 'destroyComponent'])->name('salary.destroyComponent');
    Route::get('/{employee}/salary/disbursements',          [EmployeeSalaryController::class, 'disbursements'])->name('salary.disbursements');
    Route::post('/{employee}/salary/disbursements',             [EmployeeSalaryController::class, 'storeDisbursement'])->name('salary.storeDisbursement');
    Route::delete('/{employee}/salary/disbursements/{disbursement}', [EmployeeSalaryController::class, 'destroyDisbursement'])->name('salary.destroyDisbursement');
    Route::get('/{employee}/salary/disbursements/{disbursement}/reverse',  [EmployeeSalaryController::class, 'reverseForm'])->name('salary.reverseForm');
    Route::post('/{employee}/salary/disbursements/{disbursement}/reverse', [EmployeeSalaryController::class, 'reverse'])->name('salary.reverse');
    Route::post('/{employee}/salary/bonuses',               [EmployeeSalaryController::class, 'storeBonus'])->name('salary.storeBonus');
    Route::delete('/{employee}/salary/bonuses/{bonus}',     [EmployeeSalaryController::class, 'destroyBonus'])->name('salary.destroyBonus');
    Route::post('/{employee}/salary/advances',              [EmployeeSalaryController::class, 'storeAdvance'])->name('salary.storeAdvance');
    Route::patch('/{employee}/advances/{advance}/recovery', [EmployeeSalaryController::class, 'updateAdvanceRecovery'])->name('salary.updateAdvanceRecovery');

    // Documents
    Route::post('/{employee}/documents',            [EmployeeController::class, 'storeDocument'])->name('documents.store');
    Route::delete('/{employee}/documents/{document}', [EmployeeController::class, 'destroyDocument'])->name('documents.destroy');

    // Employee CRUD
    Route::resource('/', EmployeeController::class)->parameters(['' => 'employee']);
});

// ── CENTER routes ────────────────────────────────────────────────────
Route::prefix('center')->name('center.')->group(function () {

    // Auth (guest only)
    Route::middleware('guest:center')->group(function () {
        Route::get('login',  [CenterAuthController::class, 'loginForm'])->name('login');
        Route::post('login', [CenterAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    });

    // OTP verification (session-gated, no guard needed)
    Route::get('otp',        [CenterAuthController::class, 'showOtpForm'])->name('otp.form');
    Route::post('otp/verify',[CenterAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
    Route::post('otp/resend',[CenterAuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');

    // Authenticated
    Route::middleware('role.auth:center')->group(function () {
        Route::post('logout',          [CenterAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard',        [CenterAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('profile',          [CenterAuthController::class, 'profile'])->name('profile');
        Route::get('change-password',  [CenterAuthController::class, 'changePasswordForm'])->name('change-password');
        Route::post('change-password', [CenterAuthController::class, 'changePassword'])->name('change-password.update');

        // Students (only if can_view_students)
        Route::get('students/search',   [CenterStudentController::class, 'globalSearch'])->name('students.search');
        Route::get('students/export',   [CenterStudentController::class, 'export'])->name('students.export');
        Route::get('students',          [CenterStudentController::class, 'index'])->name('students.index');
        Route::get('students/{student}',[CenterStudentController::class, 'show'])->name('students.show');

        // Admissions (only if can_add_admission)
        Route::get('admissions/quick', [CenterStudentController::class, 'quickCreate'])->name('admissions.quick-create');
        Route::post('admissions/quick',[CenterStudentController::class, 'quickStore'])->name('admissions.quick-store');
        Route::get('admissions/quick-edit', [CenterStudentController::class, 'quickEditPreview'])->name('admissions.quick-edit-preview');
        Route::post('admissions/quick-confirm', [CenterStudentController::class, 'quickConfirm'])->name('admissions.quick-confirm');
        Route::get('admissions/stream-subjects', [CenterStudentController::class, 'getStreamSubjects'])->name('admissions.stream-subjects');
        Route::get('admissions/stream-seats', [CenterStudentController::class, 'getStreamSeats'])->name('admissions.stream-seats');
        Route::post('admissions/fee-preview', [CenterStudentController::class, 'feePreview'])->name('admissions.fee-preview');
        Route::get('admissions/new',   [CenterStudentController::class, 'create'])->name('admissions.create');
        Route::post('admissions',      [CenterStudentController::class, 'store'])->name('admissions.store');
        Route::get('admissions/edit-preview', [CenterStudentController::class, 'editPreview'])->name('admissions.edit-preview');
        Route::post('admissions/confirm', [CenterStudentController::class, 'confirm'])->name('admissions.confirm');
        Route::get('admissions/{student}/success', [CenterStudentController::class, 'quickSuccess'])->name('admissions.quick-success');
        Route::get('admissions/{student}/upload-documents', [AdmissionDocumentController::class, 'uploadPage'])->name('admissions.upload-documents');
        Route::get('admissions/{student}/fee-payment', [CenterStudentController::class, 'feePayment'])->name('admissions.fee-payment');
        Route::get('admissions/{student}/skip-fee-payment', [CenterStudentController::class, 'skipFeePayment'])->name('admissions.skip-fee-payment');
        Route::get('admissions/{student}/print-all', [CenterStudentController::class, 'printAll'])->name('admissions.print-all');
        Route::get('admissions/{student}/print-all/{invoice}', [CenterStudentController::class, 'printAll'])->name('admissions.print-all-receipt');

        // Fee (only if can_collect_fee)
        Route::get('fee',                             [CenterFeeController::class, 'index'])->name('fee.index');
        Route::get('fee/collect',                     [CenterFeeController::class, 'create'])->name('fee.create');
        Route::post('fee/collect',                    [CenterFeeController::class, 'store'])->name('fee.store');
        Route::get('fee/search-student',              [CenterFeeController::class, 'searchStudent'])->name('fee.search-student');
        Route::get('fee/export',                      [CenterFeeController::class, 'export'])->name('fee.export');
        Route::get('fee/{student}/receipt/{invoice}', [CenterFeeController::class, 'receipt'])->name('fee.receipt');
        Route::get('fee/{student}/history',           [CenterFeeController::class, 'studentHistory'])->name('fee.student-history');
        Route::get('fee/{student}/wallet',            [CenterFeeController::class, 'studentWallet'])->name('fee.wallet.student');

        // Admission Documents
        Route::prefix('admission-documents')->name('admission.documents.')->group(function () {
            Route::post('upload/{student}',     [AdmissionDocumentController::class, 'upload'])->name('upload');
            Route::get('for-course',            [AdmissionDocumentController::class, 'getForCourse'])->name('for-course');
            Route::get('{document}',            [AdmissionDocumentController::class, 'show'])->name('show');
        });

        // Reports (only if can_download_reports)
        Route::get('reports',                [CenterReportController::class, 'index'])->name('reports.index');
        Route::get('reports/students',       [CenterReportController::class, 'downloadStudents'])->name('reports.students');
        Route::get('reports/admissions',     [CenterReportController::class, 'downloadAdmissions'])->name('reports.admissions');
        Route::get('reports/fee-collection', [CenterReportController::class, 'downloadFeeCollection'])->name('reports.fee-collection');

        // Wallet status & extension request
        Route::get('fee/wallet',                    [CenterFeeController::class, 'walletStatus'])->name('fee.wallet.status');
        Route::post('fee/wallet/request-extension', [CenterFeeController::class, 'requestExtension'])->name('fee.wallet.request-extension');

        // Notices
        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/',                [\App\Http\Controllers\Center\CenterNoticeController::class, 'index'])->name('index');
            Route::post('/{notice}/read',  [\App\Http\Controllers\Center\CenterNoticeController::class, 'markRead'])->name('read');
        });
    });
});

// ── STAFF routes ─────────────────────────────────────────────────────
Route::prefix('staff')->name('staff.')->group(function () {

    Route::middleware('guest:staff')->group(function () {
        Route::get('login',  [StaffAuthController::class, 'loginForm'])->name('login');
        Route::post('login', [StaffAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
        Route::get('otp-verify',  [StaffAuthController::class, 'showOtpForm'])->name('otp.form');
        Route::post('otp-verify', [StaffAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
        Route::post('otp-resend', [StaffAuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');
    });

    Route::middleware('role.auth:staff')->group(function () {
        Route::post('logout',          [StaffAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard',        [StaffAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('profile',          [StaffAuthController::class, 'profile'])->name('profile');
        Route::get('change-password',  [StaffAuthController::class, 'changePasswordForm'])->name('change-password');
        Route::post('change-password', [StaffAuthController::class, 'changePassword'])->name('change-password.update');

        // Admissions (permission: admission_add)
        Route::get('students/search', [StaffAdmissionController::class, 'globalSearch'])->name('students.search');
        Route::get('admissions/quick', [StaffAdmissionController::class, 'quickCreate'])->name('admissions.quick-create');
        Route::post('admissions/quick',[StaffAdmissionController::class, 'quickStore'])->name('admissions.quick-store');
        Route::get('admissions/quick-edit', [StaffAdmissionController::class, 'quickEditPreview'])->name('admissions.quick-edit-preview');
        Route::post('admissions/quick-confirm', [StaffAdmissionController::class, 'quickConfirm'])->name('admissions.quick-confirm');
        Route::get('admissions/stream-subjects', [StaffAdmissionController::class, 'getStreamSubjects'])->name('admissions.stream-subjects');
        Route::get('admissions/stream-seats', [StaffAdmissionController::class, 'getStreamSeats'])->name('admissions.stream-seats');
        Route::post('admissions/fee-preview', [StaffAdmissionController::class, 'feePreview'])->name('admissions.fee-preview');
        Route::get('admissions/new',   [StaffAdmissionController::class, 'create'])->name('admissions.create');
        Route::post('admissions',      [StaffAdmissionController::class, 'store'])->name('admissions.store');
        Route::get('admissions/edit-preview', [StaffAdmissionController::class, 'editPreview'])->name('admissions.edit-preview');
        Route::post('admissions/confirm', [StaffAdmissionController::class, 'confirm'])->name('admissions.confirm');
        Route::get('admissions',       [StaffAdmissionController::class, 'index'])->name('admissions.index');
        Route::get('admissions/approvals', [StaffAdmissionController::class, 'approvals'])->name('admissions.approvals.index');
        Route::get('admissions/approvals/{student}', [StaffAdmissionController::class, 'approvalShow'])->name('admissions.approvals.show');
        Route::post('admissions/approvals/{student}/approve', [StaffAdmissionController::class, 'approveAdmission'])->name('admissions.approvals.approve');
        Route::post('admissions/approvals/{student}/status', [StaffAdmissionController::class, 'updateApprovalStatus'])->name('admissions.approvals.status');
        Route::get('admissions/{student}/success', [StaffAdmissionController::class, 'quickSuccess'])->name('admissions.quick-success');
        Route::get('admissions/{student}/upload-documents', [AdmissionDocumentController::class, 'uploadPage'])->name('admissions.upload-documents');
        Route::get('admissions/{student}/fee-payment', [StaffAdmissionController::class, 'feePayment'])->name('admissions.fee-payment');
        Route::get('admissions/{student}/skip-fee-payment', [StaffAdmissionController::class, 'skipFeePayment'])->name('admissions.skip-fee-payment');
        Route::get('admissions/{student}/print-form', [StaffAdmissionController::class, 'printForm'])->name('admissions.print-form');
        Route::get('admissions/{student}/print-all', [StaffAdmissionController::class, 'printAll'])->name('admissions.print-all');
        Route::get('admissions/{student}/print-all/{invoice}', [StaffAdmissionController::class, 'printAll'])->name('admissions.print-all-receipt');
        // Student Promote (permission: student_promote) — before {student} wildcard
        Route::get('admissions/promote',           [StaffAdmissionController::class, 'promoteIndex'])->name('admissions.promote.index');
        Route::get('admissions/promote/parts',     [StaffAdmissionController::class, 'promoteParts'])->name('admissions.promote.parts');
        Route::post('admissions/promote/preview',  [StaffAdmissionController::class, 'promotePreview'])->name('admissions.promote.preview');
        Route::post('admissions/promote',          [StaffAdmissionController::class, 'promoteStore'])->name('admissions.promote.store');
        Route::post('admissions/promote/bulk',     [StaffAdmissionController::class, 'promoteBulk'])->name('admissions.promote.bulk');
        // New PromotionController routes for staff — mirrors institute group
        Route::prefix('admissions/promote')->name('admissions.promote.')->group(function () {
            Route::get ('semester',                [PromotionController::class, 'semesterIndex'])       ->name('semester');
            Route::post('semester',                [PromotionController::class, 'semesterPromote'])     ->name('semester.do');
            Route::get ('session',                 [PromotionController::class, 'sessionIndex'])        ->name('session');
            Route::post('session',                 [PromotionController::class, 'sessionPromote'])      ->name('session.do');
            Route::get ('report',                  [PromotionController::class, 'report'])              ->name('report');
            Route::get ('outcomes',                [PromotionController::class, 'outcomesIndex'])       ->name('outcomes');
            Route::get ('promoted-students',       [PromotionController::class, 'promotedStudents'])    ->name('promoted-students');
            Route::post('check-status',            [PromotionController::class, 'checkStudentStatus']) ->name('check-status');
            Route::post('reverse/{log}',           [PromotionController::class, 'reversePromotion'])   ->name('reverse');
            Route::get ('identity',                [PromotionController::class, 'identityIndex'])       ->name('identity');
            Route::get ('identity/template',       [PromotionController::class, 'identityTemplate'])   ->name('identity.template');
            Route::post('identity/{identity}',     [PromotionController::class, 'identityUpdate'])     ->name('identity.update');
            Route::post('identity-bulk',           [PromotionController::class, 'identityBulkUpdate']) ->name('identity.bulk');
            Route::post('mark-complete/{student}', [PromotionController::class, 'markComplete'])       ->name('mark-complete');
            Route::get ('readmit/{student}',       [PromotionController::class, 'readmitForm'])      ->name('readmit');
            Route::post('readmit/{student}',       [PromotionController::class, 'readmit'])          ->name('readmit.do');
        });

        Route::get('admissions/{student}/edit', [StaffAdmissionController::class, 'edit'])->name('admissions.edit');
        Route::patch('admissions/{student}', [StaffAdmissionController::class, 'update'])->name('admissions.update');
        Route::post('admissions/{student}/toggle-status', [StaffAdmissionController::class, 'toggleStatus'])->name('admissions.toggle-status');
        Route::delete('admissions/{student}', [StaffAdmissionController::class, 'destroy'])->name('admissions.destroy');
        Route::get('admissions/{student}', [StaffAdmissionController::class, 'show'])->name('admissions.show');
        Route::post('admissions/{student}/resend-credentials', [AdmissionController::class, 'resendCredentials'])->name('admissions.resend-credentials');

        // Admission Documents (permission checked inside controller)
        Route::prefix('admission-documents')->name('admission.documents.')->group(function () {
            Route::post('upload/{student}',     [AdmissionDocumentController::class, 'upload'])->name('upload');
            Route::post('{document}/verify',    [AdmissionDocumentController::class, 'verify'])->name('verify');
            Route::post('{document}/reject',    [AdmissionDocumentController::class, 'reject'])->name('reject');
            Route::delete('{document}',         [AdmissionDocumentController::class, 'destroy'])->name('destroy');
            Route::get('for-course',            [AdmissionDocumentController::class, 'getForCourse'])->name('for-course');
            Route::get('{document}',            [AdmissionDocumentController::class, 'show'])->name('show');
        });

        // Fee (permission: fee_collect)
        Route::get('fee/collect',                     [StaffFeeController::class, 'create'])->name('fee.create');
        Route::post('fee/collect',                    [StaffFeeController::class, 'store'])->name('fee.store');
        Route::get('fee/search-student',              [StaffFeeController::class, 'searchStudent'])->name('fee.search-student');
        Route::get('fee/export',                      [StaffFeeController::class, 'export'])->name('fee.export');
        Route::get('fee/{student}/receipt/{invoice}', [StaffFeeController::class, 'receipt'])->name('fee.receipt');
        Route::get('fee/history',                     [StaffFeeController::class, 'index'])->name('fee.index');
        Route::get('fee/{student}/history',           [StaffFeeController::class, 'studentHistory'])->name('fee.student-history');
        Route::get('fee/{student}/wallet',            [StaffFeeController::class, 'studentWallet'])->name('fee.wallet.student');
        Route::post('fee/{student}/invoice/{invoice}/cancel', [StaffFeeController::class, 'cancel'])->name('fee.cancel');
        Route::get('fee/approvals',                    [FeeApprovalController::class, 'index'])->name('fee.approvals.index');
        Route::post('fee/approvals/{invoice}/approve',  [FeeApprovalController::class, 'approve'])->name('fee.approvals.approve');
        Route::post('fee/approvals/{invoice}/reject',   [FeeApprovalController::class, 'reject'])->name('fee.approvals.reject');
        Route::get('fee/practical-tokens', [PracticalFeeTokenController::class, 'index'])->name('fee.practical-tokens.index');
        Route::get('fee/practical-tokens/create', [PracticalFeeTokenController::class, 'create'])->name('fee.practical-tokens.create');
        Route::post('fee/practical-tokens', [PracticalFeeTokenController::class, 'store'])->name('fee.practical-tokens.store');
        Route::get('fee/practical-tokens/{batch}', [PracticalFeeTokenController::class, 'show'])->name('fee.practical-tokens.show');
        Route::post('fee/practical-tokens/{batch}/entries', [PracticalFeeTokenController::class, 'postEntries'])->name('fee.practical-tokens.entries.store');

        Route::prefix('library')->name('library.')->group(function () {
            Route::get('/', [LibraryDashboardController::class, 'index'])->name('dashboard');

            Route::get('categories', [LibraryCategoryController::class, 'index'])->name('categories.index');
            Route::post('categories', [LibraryCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [LibraryCategoryController::class, 'update'])->name('categories.update');
            Route::post('categories/{category}/toggle', [LibraryCategoryController::class, 'toggle'])->name('categories.toggle');
            Route::delete('categories/{category}', [LibraryCategoryController::class, 'destroy'])->name('categories.destroy');

            Route::get('authors', [LibraryAuthorController::class, 'index'])->name('authors.index');
            Route::post('authors', [LibraryAuthorController::class, 'store'])->name('authors.store');
            Route::put('authors/{author}', [LibraryAuthorController::class, 'update'])->name('authors.update');
            Route::post('authors/{author}/toggle', [LibraryAuthorController::class, 'toggle'])->name('authors.toggle');
            Route::delete('authors/{author}', [LibraryAuthorController::class, 'destroy'])->name('authors.destroy');

            Route::get('publishers', [LibraryPublisherController::class, 'index'])->name('publishers.index');
            Route::post('publishers', [LibraryPublisherController::class, 'store'])->name('publishers.store');
            Route::put('publishers/{publisher}', [LibraryPublisherController::class, 'update'])->name('publishers.update');
            Route::post('publishers/{publisher}/toggle', [LibraryPublisherController::class, 'toggle'])->name('publishers.toggle');
            Route::delete('publishers/{publisher}', [LibraryPublisherController::class, 'destroy'])->name('publishers.destroy');

            Route::get('subjects', [LibrarySubjectController::class, 'index'])->name('subjects.index');
            Route::post('subjects', [LibrarySubjectController::class, 'store'])->name('subjects.store');
            Route::put('subjects/{subject}', [LibrarySubjectController::class, 'update'])->name('subjects.update');
            Route::post('subjects/{subject}/toggle', [LibrarySubjectController::class, 'toggle'])->name('subjects.toggle');
            Route::delete('subjects/{subject}', [LibrarySubjectController::class, 'destroy'])->name('subjects.destroy');

            Route::get('vendors', [LibraryVendorController::class, 'index'])->name('vendors.index');
            Route::post('vendors', [LibraryVendorController::class, 'store'])->name('vendors.store');
            Route::put('vendors/{vendor}', [LibraryVendorController::class, 'update'])->name('vendors.update');
            Route::post('vendors/{vendor}/toggle', [LibraryVendorController::class, 'toggle'])->name('vendors.toggle');
            Route::delete('vendors/{vendor}', [LibraryVendorController::class, 'destroy'])->name('vendors.destroy');

            Route::get('racks', [LibraryRackController::class, 'index'])->name('racks.index');
            Route::post('racks', [LibraryRackController::class, 'store'])->name('racks.store');
            Route::put('racks/{rack}', [LibraryRackController::class, 'update'])->name('racks.update');
            Route::post('racks/{rack}/toggle', [LibraryRackController::class, 'toggle'])->name('racks.toggle');
            Route::delete('racks/{rack}', [LibraryRackController::class, 'destroy'])->name('racks.destroy');

            Route::get('rules', [LibraryRuleSetController::class, 'index'])->name('rules.index');
            Route::post('rules', [LibraryRuleSetController::class, 'store'])->name('rules.store');
            Route::put('rules/{rule}', [LibraryRuleSetController::class, 'update'])->name('rules.update');
            Route::post('rules/{rule}/toggle', [LibraryRuleSetController::class, 'toggle'])->name('rules.toggle');
            Route::delete('rules/{rule}', [LibraryRuleSetController::class, 'destroy'])->name('rules.destroy');

            Route::get('books', [LibraryBookController::class, 'index'])->name('books.index');
            Route::get('books/create', [LibraryBookController::class, 'create'])->name('books.create');
            Route::post('books', [LibraryBookController::class, 'store'])->name('books.store');
            Route::get('books/{book}', [LibraryBookController::class, 'show'])->name('books.show');
            Route::get('books/{book}/labels', [LibraryBookController::class, 'labels'])->name('books.labels');
            Route::post('books/{book}/generate-barcodes', [LibraryBookController::class, 'generateBarcodes'])->name('books.generate-barcodes');
            Route::get('books/{book}/edit', [LibraryBookController::class, 'edit'])->name('books.edit');
            Route::put('books/{book}', [LibraryBookController::class, 'update'])->name('books.update');
            Route::post('books/{book}/toggle', [LibraryBookController::class, 'toggle'])->name('books.toggle');
            Route::post('books/{book}/copies', [LibraryBookController::class, 'storeCopy'])->name('books.copies.store');
            Route::put('books/{book}/copies/{copy}', [LibraryBookController::class, 'updateCopy'])->name('books.copies.update');

            Route::get('members', [LibraryMemberController::class, 'index'])->name('members.index');
            Route::post('members/sync-students', [LibraryMemberController::class, 'syncStudents'])->name('members.sync-students');
            Route::post('members/sync-staff', [LibraryMemberController::class, 'syncStaff'])->name('members.sync-staff');
            Route::put('members/{member}', [LibraryMemberController::class, 'update'])->name('members.update');

            Route::get('circulation', [LibraryCirculationController::class, 'index'])->name('circulation.index');
            Route::post('circulation/issue', [LibraryCirculationController::class, 'issue'])->name('circulation.issue');
            Route::post('circulation/{transaction}/renew', [LibraryCirculationController::class, 'renew'])->name('circulation.renew');
            Route::post('circulation/{transaction}/return', [LibraryCirculationController::class, 'return'])->name('circulation.return');
            Route::post('circulation/{transaction}/fine', [LibraryCirculationController::class, 'payFine'])->name('circulation.fine');
            Route::get('circulation/{transaction}/receipt', [LibraryCirculationController::class, 'receipt'])->name('circulation.receipt');

            Route::get('reservations', [LibraryReservationController::class, 'index'])->name('reservations.index');
            Route::post('reservations', [LibraryReservationController::class, 'store'])->name('reservations.store');
            Route::post('reservations/{reservation}/fulfill', [LibraryReservationController::class, 'fulfill'])->name('reservations.fulfill');
            Route::post('reservations/{reservation}/cancel', [LibraryReservationController::class, 'cancel'])->name('reservations.cancel');

            Route::get('reports', [LibraryReportController::class, 'index'])->name('reports.index');
            Route::get('no-due', [LibraryNoDueController::class, 'index'])->name('no-due.index');
            Route::get('no-due/{student}/print', [LibraryNoDueController::class, 'print'])->name('no-due.print');

            Route::get('fines', [LibraryFineCollectionController::class, 'index'])->name('fines.index');
            Route::get('fines/{member}', [LibraryFineCollectionController::class, 'show'])->name('fines.show');
            Route::post('fines/{member}/collect', [LibraryFineCollectionController::class, 'collect'])->name('fines.collect');
            Route::get('fines/{member}/receipt/{receiptNo}', [LibraryFineCollectionController::class, 'receipt'])->name('fines.receipt');
        });

        // Statement (permission: get_statement, statement_export)
        Route::prefix('statement')->name('statement.')->group(function () {
            Route::get('/search-student', [StatementController::class, 'searchStudent'])->name('search-student');
            Route::get('/balance',        [StatementController::class, 'studentBalance'])->name('balance');
            Route::get('/fee-record',     [StatementController::class, 'feeRecord'])->name('fee-record');
            Route::get('/export-csv',     [StatementController::class, 'exportCsv'])->name('export-csv');
        });

        // Finance (permissions: expense_create, finance_view, salary_manage)
        Route::prefix('finance')->name('finance.')->group(function () {
            Route::get('/expenses',        [StaffFinanceController::class, 'expenses'])->name('expenses.index');
            Route::get('/expenses/create', [StaffFinanceController::class, 'createExpense'])->name('expenses.create');
            Route::post('/expenses',       [StaffFinanceController::class, 'storeExpense'])->name('expenses.store');
            Route::get('ajax/sub-categories', [ExpenseCategoryAjaxController::class, 'subCategories'])->name('ajax.sub-categories');
            Route::get('ajax/vendors',        [ExpenseCategoryAjaxController::class, 'vendors'])->name('ajax.vendors');
            Route::get('/salary',                              [StaffFinanceController::class, 'salary'])->name('salary.index');
            Route::get('/salary/{salaryRecord}/pay',          [StaffFinanceController::class, 'paySalary'])->name('salary.pay');
            Route::post('/salary/{salaryRecord}/mark-paid',   [StaffFinanceController::class, 'markSalaryPaid'])->name('salary.mark-paid');

            // Finance Reports (permissions: finance_reports, ledger_view)
            Route::get('/reports/ledger',         [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'ledger'])->name('reports.ledger');
            Route::get('/reports/day-book',       [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'dayBook'])->name('reports.day-book');
            Route::get('/reports/cash-book',      [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'cashBook'])->name('reports.cash-book');
            Route::get('/reports/bank-book',      [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'bankBook'])->name('reports.bank-book');
            Route::get('/reports/trial-balance',  [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'trialBalance'])->name('reports.trial-balance');
            Route::get('/reports/profit-loss',    [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'profitAndLoss'])->name('reports.profit-loss');
            Route::get('/reports/reconciliation', [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'reconciliation'])->name('reports.reconciliation');
            Route::get('/reports/income-book',    [\App\Http\Controllers\Institute\Finance\FinanceReportController::class, 'incomeBook'])->name('reports.income-book');

            // Payroll (permissions: attendance_mark, payroll_generate, payroll_approve)
            Route::prefix('payroll')->name('payroll.')->group(function () {
                Route::get('/attendance/daily',         [StaffPayrollController::class, 'daily'])->name('attendance.daily');
                Route::post('/attendance/mark',         [StaffPayrollController::class, 'store'])->name('attendance.store');
                Route::post('/attendance/bulk-mark',    [StaffPayrollController::class, 'bulkMark'])->name('attendance.bulk-mark');
                Route::get('/attendance/monthly',       [StaffPayrollController::class, 'monthly'])->name('attendance.monthly');
                Route::post('/attendance/lock-month',   [StaffPayrollController::class, 'lockMonth'])->name('attendance.lock-month');
                Route::post('/attendance/unlock-month', [StaffPayrollController::class, 'unlockMonth'])->name('attendance.unlock-month');
                Route::post('/generate-draft',          [StaffPayrollController::class, 'generateDraft'])->name('generate-draft');
                Route::get('/draft',                    [StaffPayrollController::class, 'draftView'])->name('draft-view');
                Route::post('/approve/{salaryRecord}',  [StaffPayrollController::class, 'approveSalary'])->name('approve');
            });
        });

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/fee-due-list',        [ReportController::class, 'feeDueList'])->name('fee-due-list');
            Route::get('/fee-collection',      [ReportController::class, 'feeCollectionReport'])->name('fee-collection');
            Route::get('/cancelled-fee',       [ReportController::class, 'cancelledFeeReport'])->name('cancelled-fee');
            Route::get('/admission',           [ReportController::class, 'admissionReport'])->name('admission');
            Route::get('/daily-collection',    [ReportController::class, 'dailyReport'])->name('daily-collection');
            Route::get('/semester-wise',       [ReportController::class, 'semesterReport'])->name('semester-wise');
            Route::get('/custom-student',      [ReportController::class, 'customStudentReport'])->name('custom-student');
            Route::get('/streams',             [ReportController::class, 'getStreams'])->name('streams');
        });

        // Notices (permission: notice_post)
        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/',                    [\App\Http\Controllers\Staff\StaffNoticeController::class, 'index'])->name('index');
            Route::get('/create',              [\App\Http\Controllers\Staff\StaffNoticeController::class, 'create'])->name('create');
            Route::post('/',                   [\App\Http\Controllers\Staff\StaffNoticeController::class, 'store'])->name('store');
            Route::get('/{notice}/edit',       [\App\Http\Controllers\Staff\StaffNoticeController::class, 'edit'])->name('edit');
            Route::put('/{notice}',            [\App\Http\Controllers\Staff\StaffNoticeController::class, 'update'])->name('update');
            Route::delete('/{notice}',         [\App\Http\Controllers\Staff\StaffNoticeController::class, 'destroy'])->name('destroy');
            Route::patch('/{notice}/toggle',   [\App\Http\Controllers\Staff\StaffNoticeController::class, 'toggle'])->name('toggle');
            Route::patch('/{notice}/pin',      [\App\Http\Controllers\Staff\StaffNoticeController::class, 'pin'])->name('pin');
            Route::post('/{notice}/read',      [\App\Http\Controllers\Staff\StaffNoticeController::class, 'markRead'])->name('read');
            Route::get('/{notice}/reads',      [\App\Http\Controllers\Staff\StaffNoticeController::class, 'readDetail'])->name('reads');
        });

        // Staff Management (permission: staff_manage)
        Route::prefix('staff-manage')->name('staff-manage.')->group(function () {
            Route::get('/',          [\App\Http\Controllers\Staff\StaffManageController::class, 'index'])->name('index');
            Route::get('/{staffMember}', [\App\Http\Controllers\Staff\StaffManageController::class, 'show'])->name('show');
        });
    });
});

// ── CHANNEL PARTNER routes ───────────────────────────────────────────
Route::prefix('partner')->name('partner.')->group(function () {

    Route::middleware('guest:partner')->group(function () {
        Route::get('login',  [PartnerAuthController::class, 'loginForm'])->name('login');
        Route::post('login', [PartnerAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    });

    // OTP verification (session-gated, no guard needed)
    Route::get('otp',        [PartnerAuthController::class, 'showOtpForm'])->name('otp.form');
    Route::post('otp/verify',[PartnerAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
    Route::post('otp/resend',[PartnerAuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');

    Route::middleware('role.auth:partner')->group(function () {
        Route::post('logout',          [PartnerAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard',        [PartnerAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('profile',          [PartnerAuthController::class, 'profile'])->name('profile');
        Route::get('change-password',  [PartnerAuthController::class, 'changePasswordForm'])->name('change-password');
        Route::post('change-password', [PartnerAuthController::class, 'changePassword'])->name('change-password.update');

        // Students (only if can_view_students)
        Route::get('students',           [PartnerStudentController::class, 'index'])->name('students.index');
        Route::get('students/search',    [PartnerStudentController::class, 'globalSearch'])->name('students.search');
        Route::get('students/{student}', [PartnerStudentController::class, 'show'])->name('students.show');

        // Admissions (only if can_add_admission)
        Route::get('admissions/quick',         [PartnerStudentController::class, 'quickCreate'])->name('admissions.quick-create');
        Route::post('admissions/quick',        [PartnerStudentController::class, 'quickStore'])->name('admissions.quick-store');
        Route::get('admissions/quick-edit',    [PartnerStudentController::class, 'quickEditPreview'])->name('admissions.quick-edit-preview');
        Route::post('admissions/quick-confirm',[PartnerStudentController::class, 'quickConfirm'])->name('admissions.quick-confirm');
        Route::get('admissions/stream-subjects',[PartnerStudentController::class, 'getStreamSubjects'])->name('admissions.stream-subjects');
        Route::get('admissions/stream-seats',  [PartnerStudentController::class, 'getStreamSeats'])->name('admissions.stream-seats');
        Route::post('admissions/fee-preview',  [PartnerStudentController::class, 'feePreview'])->name('admissions.fee-preview');
        Route::get('admissions/new',           [PartnerStudentController::class, 'create'])->name('admissions.create');
        Route::post('admissions',              [PartnerStudentController::class, 'store'])->name('admissions.store');
        Route::get('admissions/edit-preview',  [PartnerStudentController::class, 'editPreview'])->name('admissions.edit-preview');
        Route::post('admissions/confirm',      [PartnerStudentController::class, 'confirm'])->name('admissions.confirm');
        Route::get('admissions/{student}/success', [PartnerStudentController::class, 'quickSuccess'])->name('admissions.quick-success');
        Route::get('admissions/{student}/upload-documents', [AdmissionDocumentController::class, 'uploadPage'])->name('admissions.upload-documents');
        Route::get('admissions/{student}/fee-payment', [PartnerStudentController::class, 'feePayment'])->name('admissions.fee-payment');
        Route::get('admissions/{student}/skip-fee-payment', [PartnerStudentController::class, 'skipFeePayment'])->name('admissions.skip-fee-payment');
        Route::get('admissions/{student}/print-all', [PartnerStudentController::class, 'printAll'])->name('admissions.print-all');
        Route::get('admissions/{student}/print-all/{invoice}', [PartnerStudentController::class, 'printAll'])->name('admissions.print-all-receipt');

        // Admission Documents
        Route::prefix('admission-documents')->name('admission.documents.')->group(function () {
            Route::post('upload/{student}',     [AdmissionDocumentController::class, 'upload'])->name('upload');
            Route::get('for-course',            [AdmissionDocumentController::class, 'getForCourse'])->name('for-course');
            Route::get('{document}',            [AdmissionDocumentController::class, 'show'])->name('show');
        });

        // Fee (only if can_collect_fee)
        Route::get('fee/collections',                 [PartnerFeeController::class, 'index'])->name('fee.index');
        Route::get('fee/collect',                     [PartnerFeeController::class, 'create'])->name('fee.create');
        Route::post('fee/collect',                    [PartnerFeeController::class, 'store'])->name('fee.store');
        Route::get('fee/search-student',              [PartnerFeeController::class, 'searchStudent'])->name('fee.search-student');
        Route::get('fee/{student}/receipt/{invoice}', [PartnerFeeController::class, 'receipt'])->name('fee.receipt');
        Route::get('fee/{student}/history',           [PartnerFeeController::class, 'studentHistory'])->name('fee.student-history');
        Route::get('fee/{student}/wallet',            [PartnerFeeController::class, 'studentWallet'])->name('fee.wallet.student');

        // Reports (only if can_download_reports)
        Route::get('reports',                [PartnerReportController::class, 'index'])->name('reports.index');
        Route::get('reports/students',       [PartnerReportController::class, 'downloadStudents'])->name('reports.students');
        Route::get('reports/admissions',     [PartnerReportController::class, 'downloadAdmissions'])->name('reports.admissions');
        Route::get('reports/fee-collection', [PartnerReportController::class, 'downloadFeeCollection'])->name('reports.fee-collection');

        // Wallet status & extension request
        Route::get('fee/wallet',                    [PartnerFeeController::class, 'walletStatus'])->name('fee.wallet.status');
        Route::post('fee/wallet/request-extension', [PartnerFeeController::class, 'requestExtension'])->name('fee.wallet.request-extension');

        // Notices
        Route::prefix('notices')->name('notices.')->group(function () {
            Route::get('/',                [\App\Http\Controllers\Partner\PartnerNoticeController::class, 'index'])->name('index');
            Route::post('/{notice}/read',  [\App\Http\Controllers\Partner\PartnerNoticeController::class, 'markRead'])->name('read');
        });
    });
});

// ── LIBRARY STAFF portal routes ──────────────────────────────────────
Route::prefix('library-staff')->name('library_staff.')->group(function () {

    // Guest-only (login/OTP pages)
    Route::middleware('guest:library_staff')->group(function () {
        Route::get('login',  [LibraryStaffAuthController::class, 'loginForm'])->name('login');
        Route::post('login', [LibraryStaffAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    });

    // OTP verification (session-gated, no guard check — pending login)
    Route::get('otp',         [LibraryStaffAuthController::class, 'showOtpForm'])->name('otp.form');
    Route::post('otp/verify', [LibraryStaffAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
    Route::post('otp/resend', [LibraryStaffAuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');

    // Authenticated library staff
    Route::middleware(['role.auth:library_staff', 'lib.staff.session'])->group(function () {
        Route::post('logout',              [LibraryStaffAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard',            [LibraryStaffAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('portal-select',        [LibraryStaffAuthController::class, 'showPortalSelect'])->name('portal.select');
        Route::get('profile',              [LibraryStaffAuthController::class, 'profileForm'])->name('profile');
        Route::put('profile',              [LibraryStaffAuthController::class, 'updateProfile'])->name('profile.update');
        Route::get('activity',             [LibraryStaffAuthController::class, 'activityLog'])->name('activity');
    });
});

// ── Student Portal ─────────────────────────────────────────────────────────
Route::prefix('student')->name('student.')->group(function () {
    Route::middleware('guest:student')->group(function () {
        Route::get('login',  [StudentAuthController::class, 'loginForm'])->name('login');
        Route::post('login', [StudentAuthController::class, 'login'])->name('login.submit')->middleware('throttle:5,1');
    });

    Route::get('otp',         [StudentAuthController::class, 'showOtpForm'])->name('otp.form');
    Route::post('otp/verify', [StudentAuthController::class, 'verifyOtp'])->name('otp.verify')->middleware('throttle:5,1');
    Route::post('otp/resend', [StudentAuthController::class, 'resendOtp'])->name('otp.resend')->middleware('throttle:3,1');

    Route::middleware('role.auth:student')->group(function () {
        Route::post('logout',              [StudentAuthController::class, 'logout'])->name('logout');
        Route::get('dashboard',            [StudentAuthController::class, 'dashboard'])->name('dashboard');
        Route::get('change-password',      [StudentAuthController::class, 'changePasswordForm'])->name('change-password');
        Route::post('change-password',     [StudentAuthController::class, 'changePassword'])->name('change-password.update');
        Route::post('notices/{id}/read',   [StudentAuthController::class, 'markNoticeRead'])->name('notices.read');
    });
});
