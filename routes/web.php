<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\SupplierAuthController;
use App\Http\Controllers\ceo\CeoAccessController;
use App\Http\Controllers\ceo\CeoDashboardController;
use App\Http\Controllers\ceo\GeolocationController;
use App\Http\Controllers\client\ClientConversationController;
use App\Http\Controllers\client\ClientDashboardController;
use App\Http\Controllers\client\ClientInvoiceController;
use App\Http\Controllers\client\ClientProductsController;
use App\Http\Controllers\client\ClientProfileController;
use App\Http\Controllers\client\ClientReceivingController;
use App\Http\Controllers\client\ClientSupportController;
use App\Http\Controllers\client\OrdersController;
use App\Http\Controllers\crm\AccessController as CrmAccessController;
use App\Http\Controllers\crm\ApprovalController;
use App\Http\Controllers\crm\CrmDashboardController;
use App\Http\Controllers\crm\CustomerProfileController;
use App\Http\Controllers\crm\InterviewController as CrmInterviewController;
use App\Http\Controllers\crm\InvestigationController;
use App\Http\Controllers\crm\LeadController;
use App\Http\Controllers\crm\TraineeController as CrmTraineeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\eco\EcoAccessController;
use App\Http\Controllers\eco\EcoCreditController;
use App\Http\Controllers\eco\EcoDashboardController;
use App\Http\Controllers\eco\EcoInquiryController;
use App\Http\Controllers\eco\EcoPushController;
use App\Http\Controllers\eco\EcoStoreController;
use App\Http\Controllers\eco\EcoSupplierController;
use App\Http\Controllers\fin\FinDashboardController;
use App\Http\Controllers\hrm\AccessController;
use App\Http\Controllers\hrm\AnalyticsController;
use App\Http\Controllers\hrm\ApplicantController as HrmApplicantController;
use App\Http\Controllers\hrm\EmployeeController;
use App\Http\Controllers\hrm\HrmDashboardController;
use App\Http\Controllers\hrm\InterviewController;
use App\Http\Controllers\hrm\OnboardingController;
use App\Http\Controllers\hrm\PayrollController;
use App\Http\Controllers\hrm\PayrollRatesController;
use App\Http\Controllers\hrm\PositionController;
use App\Http\Controllers\hrm\TraineeController;
use App\Http\Controllers\inv\BomController;
use App\Http\Controllers\inv\CheckerController;
use App\Http\Controllers\inv\InvAccessController;
use App\Http\Controllers\inv\InvDashboardController;
use App\Http\Controllers\inv\MaterialController;
use App\Http\Controllers\inv\ProductController;
use App\Http\Controllers\it\ItDashboardController;
use App\Http\Controllers\logistics\ConductorController;
use App\Http\Controllers\logistics\DispatchController;
use App\Http\Controllers\logistics\DriverController;
use App\Http\Controllers\logistics\DriversController;
use App\Http\Controllers\logistics\FleetController;
use App\Http\Controllers\logistics\InterviewController as LogisticsInterviewController;
use App\Http\Controllers\logistics\LoadController;
use App\Http\Controllers\logistics\LogAccessController;
use App\Http\Controllers\logistics\LogisticsDashboardController;
use App\Http\Controllers\logistics\ProofController;
use App\Http\Controllers\logistics\ReportController;
use App\Http\Controllers\logistics\RoutesController;
use App\Http\Controllers\logistics\TraineeController as LogisticsTraineeController;
use App\Http\Controllers\man\ManAccessController;
use App\Http\Controllers\man\Manager\ManufacturingManagerController;
use App\Http\Controllers\man\ManDashboardController;
use App\Http\Controllers\man\ManufacturingInventoryController; // <-- ADDED
use App\Http\Controllers\man\Staff\CheckerQualityController;
use App\Http\Controllers\man\Staff\DyeingColorController;
use App\Http\Controllers\man\Staff\DyeingFabricSoftenerController;
use App\Http\Controllers\man\Staff\DyeingFormingController;
use App\Http\Controllers\man\Staff\DyeingIroningController;
use App\Http\Controllers\man\Staff\DyeingPackagingController;
use App\Http\Controllers\man\Staff\DyeingSqueezerController;
use App\Http\Controllers\man\Staff\KnittingYarnController;
use App\Http\Controllers\man\Staff\MaintenanceCheckerController;
use App\Http\Controllers\ord\OrdAccessController;
use App\Http\Controllers\ord\OrdDeliveryController;
use App\Http\Controllers\ord\OrdOrdersController;
use App\Http\Controllers\ord\OrdProductionsController;
use App\Http\Controllers\pro\ProcurementController;
use App\Http\Controllers\pro\ProDashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\proj\ProjDashboardController;
use App\Http\Controllers\scm\ScmAccessController;
use App\Http\Controllers\scm\ScmProcurementOrderController;
use App\Http\Controllers\scm\ScmSalesOrderController;
use App\Http\Controllers\scm\ScmVendorController;
use App\Http\Controllers\SUPPLIERS\SupplierDashboardController;
use App\Http\Controllers\trainee\TraineeAttendanceController;
use App\Http\Controllers\trainee\TraineePayslipController;
use App\Http\Controllers\trainee\TraineeTimeKeepingController;
use App\Http\Controllers\users\AppController;
use App\Http\Controllers\users\ClockController;
use App\Http\Controllers\users\leaveController as UserLeaveController;
use App\Http\Controllers\warehouse\AccessController as WarehouseAccessController;
use App\Http\Controllers\warehouse\MonitorController;
use App\Http\Controllers\warehouse\PackageController;
use App\Http\Controllers\warehouse\ReceivingController;
use App\Http\Controllers\warehouse\RejectController;
use App\Http\Controllers\warehouse\WarehouseController;
use App\Http\Controllers\workforce\AbsentController;
use App\Http\Controllers\workforce\AccessController as WorkforceAccessController;
use App\Http\Controllers\workforce\LeaveController as WorkforceLeaveController;
use App\Http\Controllers\workforce\SchedulerController;
use App\Http\Controllers\workforce\WorkforceDashboardController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Application Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return inertia('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Career Application Routes
|--------------------------------------------------------------------------
*/
Route::get('/apply', function () {
    return inertia('Auth/apply');
})->name('apply');

Route::post('/apply/store', [HrmApplicantController::class, 'store'])->name('applicants.public.store');

/*
|--------------------------------------------------------------------------
| Authenticated User Core Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
|--------------------------------------------------------------------------
| Unified Employee UI Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'position:staff,manager'])->prefix('dashboard/employee-ui')->group(function () {
    Route::get('/', [AppController::class, 'index'])->name('employee.ui.dashboard');
    Route::get('/clock', [ClockController::class, 'clock'])->name('employee.ui.clock');
    Route::post('/clock/toggle', [ClockController::class, 'toggle'])->name('employee.attendance.toggle');
    Route::get('/leave', [UserLeaveController::class, 'leave'])->name('employee.ui.leave');
    Route::post('/leave', [UserLeaveController::class, 'store'])->name('employee.leave.store');
    Route::get('/payslip', function () {
        return inertia('Dashboard/USERS/payslip');
    })->name('employee.ui.payslip');
});

/*
|--------------------------------------------------------------------------
| Unified Trainee Portal Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/trainee')->middleware(['auth', 'verified', 'position:trainee'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('trainee.dashboard');
    Route::get('/timekeeping', [TraineeTimeKeepingController::class, 'index'])->name('trainee.timekeeping');
    Route::post('/timekeeping/clock', [TraineeTimeKeepingController::class, 'clockInOut'])->name('trainee.timekeeping.clock');
    Route::get('/attendance', [TraineeAttendanceController::class, 'index'])->name('trainee.attendance');
    Route::get('/payslip', [TraineePayslipController::class, 'index'])->name('trainee.payslip');
    Route::get('/payslip/{payroll}', [TraineePayslipController::class, 'show'])->name('trainee.payslip.show');
});

/*
|--------------------------------------------------------------------------
| Human Resources Management (HRM) Routes
|--------------------------------------------------------------------------
*/
Route::get('/active-positions', [PositionController::class, 'getActivePositions'])->name('positions.active');
Route::prefix('dashboard/hrm')->name('hrm.')->middleware(['auth', 'verified'])->group(function () {
    // Dashboard (page: dashboard)
    Route::get('/', [HrmDashboardController::class, 'index'])
        ->middleware('page.permission:dashboard,view')
        ->name('dashboard');

    // Employees (page: employee)
    Route::get('/employees', [EmployeeController::class, 'index'])
        ->middleware('page.permission:employee,view')
        ->name('employees.index');
    Route::get('/employees/{id}', [EmployeeController::class, 'show'])
        ->middleware('page.permission:employee,view')
        ->name('employees.show');
    Route::patch('/employees/{id}', [EmployeeController::class, 'update'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.update');
    Route::delete('/employees/{id}', [EmployeeController::class, 'toggleStatus'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.toggle-status');
    Route::post('/employees/{id}/promote-to-manager', [EmployeeController::class, 'promoteToManager'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.promote-to-manager');
    Route::post('/employees/{id}/demote-to-staff', [EmployeeController::class, 'demoteToStaff'])
        ->middleware('page.permission:employee,edit')
        ->name('employees.demote-to-staff');

    // Applications (page: application)
    Route::get('/applications', [HrmApplicantController::class, 'index'])
        ->middleware('page.permission:application,view')
        ->name('applications.index');
    Route::post('/applications', [HrmApplicantController::class, 'store'])
        ->middleware('page.permission:application,edit')
        ->name('applications.store');
    Route::post('/applications/{id}/accept', [HrmApplicantController::class, 'accept'])
        ->middleware('page.permission:application,edit')
        ->name('applications.accept');
    Route::post('/applications/{id}/reject', [HrmApplicantController::class, 'reject'])
        ->middleware('page.permission:application,edit')
        ->name('applications.reject');
    Route::get('/rejected', [HrmApplicantController::class, 'rejected'])
        ->middleware('page.permission:application,view')
        ->name('applications.rejected');

    // Interview (page: interview)
    Route::get('/interview', [InterviewController::class, 'index'])
        ->middleware('page.permission:interview,view')
        ->name('interview.index');
    Route::post('/interview/{id}/schedule', [InterviewController::class, 'schedule'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.schedule');
    Route::post('/interview/{id}/pass', [InterviewController::class, 'pass'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.pass');
    Route::post('/interview/{id}/fail', [InterviewController::class, 'fail'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.fail');
    Route::post('/interview/{id}/pass-to-other', [InterviewController::class, 'passToOtherModule'])
        ->middleware('page.permission:interview,edit')
        ->name('interview.pass-to-other');

    // Trainee (page: trainee)
    Route::get('/trainee', [TraineeController::class, 'index'])
        ->middleware('page.permission:trainee,view')
        ->name('trainee.index');
    Route::post('/trainee/{id}/grade', [TraineeController::class, 'grade'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.grade');
    Route::post('/trainee/{id}/pass', [TraineeController::class, 'pass'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.pass');
    Route::post('/trainee/{id}/fail', [TraineeController::class, 'fail'])
        ->middleware('page.permission:trainee,edit')
        ->name('trainee.fail');

    // Onboarding (page: onboarding)
    Route::get('/onboarding', [OnboardingController::class, 'index'])
        ->middleware('page.permission:onboarding,view')
        ->name('onboarding.index');
    Route::post('/onboarding/{id}/convert', [OnboardingController::class, 'convert'])
        ->middleware('page.permission:onboarding,edit')
        ->name('onboarding.convert');

    // Access Control (page: access)
    Route::get('/access', [AccessController::class, 'index'])
        ->middleware('page.permission:access,view')
        ->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])
        ->middleware('page.permission:access,edit')
        ->name('access.update');

    // Payroll (page: payroll)
    Route::get('/payroll', [PayrollController::class, 'index'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll');

    // Generate Payroll Form
    Route::get('/payroll/generate', [PayrollController::class, 'create'])->name('payroll.generate');
    // Generate Payroll Submission (POST)
    Route::post('/payroll/generate', [PayrollController::class, 'generate'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.generate.store');

    Route::get('/payroll/{payroll}', [PayrollController::class, 'show'])
        ->middleware('page.permission:payroll,view')
        ->name('payroll.show');

    Route::post('/payroll/{payroll}/approve', [PayrollController::class, 'approve'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.approve');

    Route::post('/payroll/{payroll}/reject', [PayrollController::class, 'reject'])
        ->middleware('page.permission:payroll,edit')
        ->name('payroll.reject');

    // Payroll Rates (simple version)
    Route::get('/payroll.rates', [PayrollController::class, 'rates'])->name('payroll.rates');
    Route::post('/payroll-rates', [PayrollController::class, 'updateRates'])->name('payroll.rates.update');

    // Analytics (page: analytics)
    Route::get('/analytics', [AnalyticsController::class, 'index'])
        ->middleware('page.permission:analytics,view')
        ->name('analytics');

    // Positions (no page permission needed, used internally)
    Route::get('/positions', [PositionController::class, 'index']);
    Route::post('/positions', [PositionController::class, 'store'])->name('positions.store');
    Route::patch('/positions/{id}/toggle-status', [PositionController::class, 'toggleStatus'])->name('positions.toggle-status');
    Route::delete('/positions/{id}', [PositionController::class, 'destroy'])->name('positions.destroy');
    Route::patch('/positions/{id}', [PositionController::class, 'update'])->name('positions.update');

    // ------------------------------------------------------------------
    // NEW: Payroll Rates Configuration (HRM managers / CEO)
    // ------------------------------------------------------------------
    Route::prefix('payroll-rates')->name('payroll-rates.')->middleware(['role:CEO,HRM'])->group(function () {
        Route::get('/', [PayrollRatesController::class, 'index'])->name('index');
        Route::post('/set', [PayrollRatesController::class, 'storePayrollSet'])->name('store-set');
        Route::put('/set/{payrollSet}', [PayrollRatesController::class, 'updatePayrollSet'])->name('update-set');
        Route::post('/set/{payrollSet}/toggle', [PayrollRatesController::class, 'togglePayrollSet'])->name('toggle-set');
        Route::post('/contribution', [PayrollRatesController::class, 'storeContributionRate'])->name('store-contribution');
        Route::put('/contribution/{rate}', [PayrollRatesController::class, 'updateContributionRate'])->name('update-contribution');
        Route::post('/contribution/{rate}/toggle', [PayrollRatesController::class, 'toggleContributionRate'])->name('toggle-contribution');
    });
});

/*
|--------------------------------------------------------------------------
| Workforce Management Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/workforce')->name('workforce.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [WorkforceDashboardController::class, 'index'])->name('dashboard');
    Route::get('/scheduler', [SchedulerController::class, 'index'])->name('scheduler');
    Route::post('/scheduler/shift', [SchedulerController::class, 'storeShift'])->name('scheduler.shift.store');
    Route::delete('/scheduler/shift/{id}', [SchedulerController::class, 'deleteShift'])->name('scheduler.shift.destroy');
    Route::post('/scheduler/holiday', [SchedulerController::class, 'storeHoliday'])->name('scheduler.holiday.store');
    Route::delete('/scheduler/holiday/{id}', [SchedulerController::class, 'deleteHoliday'])->name('scheduler.holiday.destroy');
    Route::post('/scheduler/shift/bulk', [SchedulerController::class, 'storeBulkShift'])->name('scheduler.shift.bulk');
    Route::patch('/scheduler/holiday/{id}', [SchedulerController::class, 'updateHoliday'])->name('scheduler.holiday.update');
    Route::post('/scheduler/planner', [SchedulerController::class, 'storePlannerEvent'])->name('scheduler.planner.store');
    Route::patch('/scheduler/planner/{id}', [SchedulerController::class, 'updatePlannerEvent'])->name('scheduler.planner.update');
    Route::delete('/scheduler/planner/{id}', [SchedulerController::class, 'deletePlannerEvent'])->name('scheduler.planner.destroy');
    Route::get('/leave', [WorkforceLeaveController::class, 'index'])->name('leave');
    Route::post('/leave/{id}/approve', [WorkforceLeaveController::class, 'approve'])->name('leave.approve');
    Route::post('/leave/{id}/reject', [WorkforceLeaveController::class, 'reject'])->name('leave.reject');
    Route::get('/absent', [AbsentController::class, 'index'])->name('absent');
    Route::post('/absent/{id}/suspend', [AbsentController::class, 'suspend'])->name('absent.suspend');
    Route::get('/access', [WorkforceAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [WorkforceAccessController::class, 'update'])->name('access.update');
});

/*
|--------------------------------------------------------------------------
| Supply Chain Management (SCM) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/scm')->name('scm.')->middleware(['auth', 'verified', 'module.access:SCM'])->group(function () {
    // Sales Orders (from ECO)
    Route::get('/sales-orders', [ScmSalesOrderController::class, 'index'])->name('sales-orders');
    Route::post('/sales-orders/{order}/check-inventory', [ScmSalesOrderController::class, 'checkInventory'])->name('sales-order.check-inventory');
    Route::post('/sales-orders/{order}/push-to-production', [ScmSalesOrderController::class, 'pushToProduction'])->name('sales-order.push-to-production');
    Route::post('/sales-orders/sales/{salesOrder}/check-inventory', [ScmSalesOrderController::class, 'checkInventorySalesOrder'])->name('sales-order.check-inventory-sales');
    Route::post('/sales-orders/sales/{salesOrder}/push-to-production', [ScmSalesOrderController::class, 'pushToProductionSalesOrder'])->name('sales-order.push-to-production-sales');
    Route::get('/sales-orders/check-inventory-instant/{type}/{id}', [ScmSalesOrderController::class, 'checkInventoryInstant'])
        ->name('sales-order.check-inventory-instant');

    // Procurement Orders (requests from Inventory)
    Route::get('/procurement-orders', [ScmProcurementOrderController::class, 'index'])->name('procurement-orders');
    Route::post('/procurement-orders/{materialRequest}/send', [ScmProcurementOrderController::class, 'sendToProcurementModule'])->name('procurement-order.send');

    // Vendor Management
    Route::get('/vendors', [ScmVendorController::class, 'index'])->name('vendors');
    Route::post('/vendors/{registration}/approve', [ScmVendorController::class, 'approve'])->name('vendors.approve');
    Route::post('/vendors/{registration}/reject', [ScmVendorController::class, 'reject'])->name('vendors.reject');

    // Access Control (CEO only)
    Route::get('/access', [ScmAccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [ScmAccessController::class, 'update'])->name('access.update');
});

/*
|--------------------------------------------------------------------------
| Financial Operations (FIN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/fin')->name('fin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])->name('access.update');

    Route::get('/manager', [FinDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:FIN', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [FinDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:FIN', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Manufacturing Plant (MAN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/man')->name('man.')->middleware(['auth', 'verified', 'module.access:MAN'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    // Manager access control (supervisor management)
    // Accessible by manufacturing managers (position=manager) AND elevated secretaries/GMs with MAN module
    Route::middleware(['can.access.man.manager'])->group(function () {
        Route::get('/access/manage', [ManAccessController::class, 'index'])->name('access.manage');
        Route::post('/access/assign-supervisor', [ManAccessController::class, 'assignSupervisor'])->name('access.assign-supervisor');
        
        // Manufacturing Inventory (Production Inventory)
        Route::get('/inventory', [ManufacturingInventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/{item}/container', [ManufacturingInventoryController::class, 'updateContainer'])->name('inventory.container.update');
        Route::post('/inventory/{item}/consume', [ManufacturingInventoryController::class, 'consume'])->name('inventory.consume');
    });

    // Staff dashboard entry point (redirects to role-specific dashboard or supervisor manager dashboard)
    Route::get('/staff', [ManDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:MAN', 'position:staff'])
        ->name('employee.dashboard');

    // Manufacturing Manager Dashboard & Functions
    // Accessible by both 'manager' position users, manufacturing supervisors, AND elevated secretaries/GMs with MAN module
    Route::middleware(['can.access.man.manager'])->group(function () {
        Route::get('/', [ManufacturingManagerController::class, 'index'])->name('manager.dashboard');
        Route::get('/production', [ManufacturingManagerController::class, 'production'])->name('manager.production');
        Route::get('/rejected', [ManufacturingManagerController::class, 'rejected'])->name('manager.rejected');
        Route::post('/orders/{id}/forward-to-checker', [ManufacturingManagerController::class, 'forwardToChecker'])->name('manager.forward-to-checker');
        Route::post('/staff/{id}/update-role', [ManufacturingManagerController::class, 'updateStaffRole'])->name('manager.update-staff-role');
        Route::post('/packages/{id}/send-to-logistics', [ManufacturingManagerController::class, 'sendToLogistics'])->name('manager.send-to-logistics');

        // Quality Checker — moved from staff level to manager level.
        // Accessible by: MAN manager, manufacturing supervisors, CEO, secretary, GM.
        Route::prefix('checker-quality')->name('manager.checker-quality.')->controller(CheckerQualityController::class)
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/production', 'production')->name('production');
                Route::post('/order/{id}/check-inventory', 'checkInventory')->name('check-inventory');
                Route::post('/order/{id}/start-production', 'startProduction')->name('start-production');
                Route::post('/fabric/{id}/pass', 'passFabric')->name('pass-fabric');
                Route::post('/dye/{id}/pass', 'passDye')->name('pass-dye');
                Route::post('/softener/{id}/pass', 'passSoftener')->name('pass-softener');
                Route::post('/squeezer/{id}/pass', 'passSqueezer')->name('pass-squeezer');
                Route::post('/iron/{id}/pass', 'passIron')->name('pass-iron');
                Route::post('/form/{id}/pack', 'packForm')->name('pack-form');
                Route::post('/form/{id}/reject', 'rejectForm')->name('reject-form');
                Route::post('/package/{id}/assign-to-order', 'assignPackageToOrder')->name('assign-package');
            });
    });

    // Staff-specific routes (each role has its own sub-dashboard).
    // Note: 'position:staff' is intentionally NOT here — manufacturing supervisors
    // also have position='staff' in the DB but must be able to access all pages
    // under their assigned department. The man.role middleware handles the
    // granular access check for both staff and supervisors.
    Route::middleware(['module.access:MAN'])->group(function () {
        Route::prefix('knitting-yarn')->name('staff.knitting-yarn.')->controller(KnittingYarnController::class)
            ->middleware('man.role:knitting_yarn')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/knitting-yarn', 'knittingYarn')->name('page');
                Route::post('/fabric', 'storeFabric')->name('store-fabric');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-color')->name('staff.dyeing-color.')->controller(DyeingColorController::class)
            ->middleware('man.role:dyeing_color')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-color', 'dyeingColor')->name('page');
                Route::post('/dye', 'storeDye')->name('store-dye');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-fabric-softener')->name('staff.dyeing-fabric-softener.')->controller(DyeingFabricSoftenerController::class)
            ->middleware('man.role:dyeing_fabric_softener')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-fabric-softener', 'dyeingFabricSoftener')->name('page');
                Route::post('/soften', 'storeSoftener')->name('store-soften');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-squeezer')->name('staff.dyeing-squeezer.')->controller(DyeingSqueezerController::class)
            ->middleware('man.role:dyeing_squeezer')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-squeezer', 'dyeingSqueezer')->name('page');
                Route::post('/squeeze', 'storeSqueezer')->name('store-squeeze');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-ironing')->name('staff.dyeing-ironing.')->controller(DyeingIroningController::class)
            ->middleware('man.role:dyeing_ironing')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-ironing', 'dyeingIroning')->name('page');
                Route::post('/iron', 'storeIron')->name('store-iron');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-forming')->name('staff.dyeing-forming.')->controller(DyeingFormingController::class)
            ->middleware('man.role:dyeing_forming')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/dyeing-forming', 'dyeingForming')->name('page');
                Route::post('/form', 'storeForm')->name('store-form');
                Route::get('/reports', 'reports')->name('reports');
                Route::post('/machine-report', 'reportMachine')->name('report-machine');
            });

        Route::prefix('dyeing-packaging')->name('staff.dyeing-packaging.')->controller(DyeingPackagingController::class)
            ->middleware('man.role:dyeing_packaging')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/packaging', 'packaging')->name('page');
                Route::post('/package', 'storePackage')->name('store-package');
            });

        Route::prefix('maintenance-checker')->name('staff.maintenance-checker.')->controller(MaintenanceCheckerController::class)
            ->middleware('man.role:maintenance_checker')
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/maintenance', 'maintenance')->name('page');
                Route::post('/machine', 'storeMachine')->name('store-machine');
                Route::patch('/machine/{id}', 'updateMachineStatus')->name('update-machine');
                Route::get('/reports', 'reports')->name('reports');
                Route::patch('/report/{id}', 'resolveReport')->name('resolve-report');
            });

        Route::prefix('checker-quality')->name('staff.checker-quality.')->controller(CheckerQualityController::class)
            ->middleware(['man.role:checker_quality'])
            ->group(function () {
                Route::get('/', 'index')->name('dashboard');
                Route::get('/production', 'production')->name('production');
                Route::post('/order/{id}/check-inventory', 'checkInventory')->name('check-inventory');
                Route::post('/order/{id}/start-production', 'startProduction')->name('start-production');
                Route::post('/fabric/{id}/pass', 'passFabric')->name('pass-fabric');
                Route::post('/dye/{id}/pass', 'passDye')->name('pass-dye');
                Route::post('/softener/{id}/pass', 'passSoftener')->name('pass-softener');
                Route::post('/squeezer/{id}/pass', 'passSqueezer')->name('pass-squeezer');
                Route::post('/iron/{id}/pass', 'passIron')->name('pass-iron');
                Route::post('/form/{id}/pack', 'packForm')->name('pack-form');
                Route::post('/form/{id}/reject', 'rejectForm')->name('reject-form');
                Route::post('/package/{id}/assign-to-order', 'assignPackageToOrder')->name('assign-package');
            });
    });
});

/*
|--------------------------------------------------------------------------
| Warehouse Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/warehouse')->name('warehouse.')->middleware(['auth', 'verified', 'module.access:WAR'])->group(function () {
    // General Warehouse Management
    Route::get('/', [WarehouseController::class, 'index'])->name('index');
    Route::post('/', [WarehouseController::class, 'store'])->name('store');
    Route::put('/{warehouse}', [WarehouseController::class, 'update'])->name('update');
    Route::delete('/{warehouse}', [WarehouseController::class, 'destroy'])->name('destroy');

    // Receiving & Logistics
    Route::get('/receiving', [ReceivingController::class, 'index'])->name('receiving');
    Route::post('/receiving', [ReceivingController::class, 'receive'])->name('receiving.store');

    // Warehouse Monitor (The Grid)
    Route::get('/monitor/{warehouse}', [MonitorController::class, 'show'])->name('monitor');
    Route::post('/monitor/layout/{warehouse}', [MonitorController::class, 'updateLayout'])->name('monitor.layout');

    // DRAG & DROP ROUTE (Fixed naming)
    Route::post('/monitor/assign', [MonitorController::class, 'assignToShelf'])->name('monitor.assign');

    Route::post('/monitor/use/{stockItem}', [MonitorController::class, 'useMaterial'])->name('monitor.use');

    // Packaging & Logistics
    Route::get('/packages', [PackageController::class, 'index'])->name('packages');
    Route::post('/packages/{package}/push', [PackageController::class, 'pushToLogistics'])->name('packages.push');

    // Rejects
    Route::get('/rejects', [RejectController::class, 'index'])->name('rejects');

    // Access Control
    Route::get('/access', [WarehouseAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [WarehouseAccessController::class, 'update'])->name('access.update');
});

/*
|--------------------------------------------------------------------------
| Inventory Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/inventory')->name('inv.')->middleware(['auth', 'verified', 'module.access:INV'])->group(function () {
    Route::get('/', [InvDashboardController::class, 'managerDashboard'])->name('dashboard');
    Route::get('/materials', [MaterialController::class, 'material'])->name('materials');
    Route::post('/materials', [MaterialController::class, 'store'])->name('materials.store');
    Route::delete('/materials/{id}', [MaterialController::class, 'destroy'])->name('materials.destroy');

    Route::get('/products', [ProductController::class, 'product'])->name('products');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::post('/products/{id}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::delete('/products/image/{imageId}', [ProductController::class, 'destroyImage'])->name('products.image.destroy');

    Route::get('/bom', [BomController::class, 'index'])->name('bom');
    Route::post('/bom', [BomController::class, 'store'])->name('bom.store');
    Route::put('/bom/{id}', [BomController::class, 'update'])->name('bom.update');
    Route::delete('/bom/{id}', [BomController::class, 'destroy'])->name('bom.destroy');

    // Checker & Procurement
    Route::prefix('checker')->name('checker.')->group(function () {
        Route::get('/', [CheckerController::class, 'index'])->name('dashboard');
        
        // Single Procurement Request
        Route::post('/procurement/{material}', [CheckerController::class, 'requestProcurement'])->name('procurement');
        
        // BULK PROCUREMENT REQUEST (Fixed: Added this for your Vue Component)
        Route::post('/bulk-procurement', [CheckerController::class, 'bulkProcure'])->name('bulk_procurement');
        
        Route::post('/order/{order}', [CheckerController::class, 'checkOrder'])->name('order');
    });
    
    // ✅ FIXED: Use MaterialController@procurement for creating procurement requests
    Route::post('/checker/procurement/{material}', [MaterialController::class, 'procurement'])->name('checker.procurement');
    Route::post('/checker/order/{order}', [CheckerController::class, 'checkOrder'])->name('checker.order');

    Route::get('/access', [InvAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [InvAccessController::class, 'update'])->name('access.update');

    Route::get('/product', [ProductController::class, 'product'])->name('manager.product');
    Route::post('/product', [ProductController::class, 'store'])->name('manager.product.store');
    Route::post('/product/{id}/update', [ProductController::class, 'update'])->name('manager.product.update');
    Route::delete('/product/image/{imageId}', [ProductController::class, 'destroyImage'])->name('manager.product.image.destroy');
    Route::delete('/product/{id}', [ProductController::class, 'destroy'])->name('manager.product.destroy');

    // Edit view for material
    Route::patch('/materials/{material}', [MaterialController::class, 'update'])->name('materials.update');
});

/*
|--------------------------------------------------------------------------
| Order Processing (ORD) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/ord')->name('ord.')->middleware(['auth', 'verified', 'module.access:ORD'])->group(function () {
    // Legacy HRM-style routes (keep for backward compatibility)
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    // Staff access control page (keep original name for sidebar)
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');
    Route::post('/access/update', [AccessController::class, 'update'])->name('access.update');

    // NEW Order Management Core Pages
    Route::get('/orders', [OrdOrdersController::class, 'index'])->name('orders');
    Route::get('/productions', [OrdProductionsController::class, 'index'])->name('productions');
    Route::get('/delivery', [OrdDeliveryController::class, 'index'])->name('delivery');
    Route::get('/delivery/{id}/track', [OrdDeliveryController::class, 'track'])->name('delivery.track');

    // CEO Access Control - unique name to avoid conflict
    Route::get('/access-control', [OrdAccessController::class, 'index'])->name('ceo-access.index');
    Route::post('/access-control/update', [OrdAccessController::class, 'update'])->name('ceo-access.update');
});

/*
|--------------------------------------------------------------------------
| LOGISTICS DASHBOARD & ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'module.access:LOG'])->prefix('dashboard/logistics')->name('logistics.')->group(function () {
    // Main Dashboard
    Route::get('/', [LogisticsDashboardController::class, 'index'])->name('dashboard');

    // Fleet Management
    Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
    Route::post('/fleet', [FleetController::class, 'store'])->name('fleet.store');
    Route::patch('/fleet/{truck}', [FleetController::class, 'update'])->name('fleet.update');
    Route::delete('/fleet/{truck}', [FleetController::class, 'destroy'])->name('fleet.destroy');

    // Drivers & Conductors
    Route::get('/drivers', [DriversController::class, 'index'])->name('drivers.index');
    Route::post('/drivers', [DriversController::class, 'store'])->name('drivers.store');
    Route::get('/drivers/{driver}', [DriversController::class, 'show'])->name('drivers.show');

    // Loading & Dispatch
    Route::get('/load', [LoadController::class, 'index'])->name('load.index');
    Route::post('/load/dispatch', [LoadController::class, 'passToDispatch'])->name('load.pass');

    Route::get('/dispatch', [DispatchController::class, 'index'])->name('dispatch.index');
    Route::post('/dispatch/{delivery}', [DispatchController::class, 'assignAndDispatch'])->name('dispatch.assign');

    // Portals (Driver & Conductor)
    Route::get('/driver-portal', [DriverController::class, 'index'])->name('driver.portal');
    Route::post('/driver-portal/{delivery}/transit', [DriverController::class, 'markInTransit'])->name('driver.transit');
    Route::post('/driver-portal/{delivery}/proof', [DriverController::class, 'uploadProof'])->name('driver.proof');

    Route::get('/conductor-portal', [ConductorController::class, 'index'])->name('conductor.portal');
    Route::post('/conductor-portal/{delivery}/report', [ConductorController::class, 'storeReport'])->name('conductor.report');

    // Reports & Access
    Route::get('/proof-of-delivery', [ProofController::class, 'index'])->name('proof.index');
    Route::get('/conductor-reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/access-control', [LogAccessController::class, 'index'])->name('access.index');
    Route::post('/access-control', [LogAccessController::class, 'update'])->name('access.update');

    Route::get('/routes', [RoutesController::class, 'index'])->name('routes');

    // ========== INTERVIEWS & TRAINEES (LOGISTICS) ==========
    // Page permission middleware removed – any LOG module user can manage interviews and trainees
    Route::get('/interview', [LogisticsInterviewController::class, 'index'])->name('interview.index');
    Route::post('/interview/{id}/schedule', [LogisticsInterviewController::class, 'schedule'])->name('interview.schedule');
    Route::post('/interview/{id}/pass', [LogisticsInterviewController::class, 'pass'])->name('interview.pass');
    Route::post('/interview/{id}/fail', [LogisticsInterviewController::class, 'fail'])->name('interview.fail');
    Route::post('/interview/{id}/pass-to-other', [LogisticsInterviewController::class, 'passToOtherModule'])->name('interview.pass-to-other');

    Route::get('/trainee', [LogisticsTraineeController::class, 'index'])->name('trainee.index');
    Route::post('/trainee/{id}/grade', [LogisticsTraineeController::class, 'grade'])->name('trainee.grade');
    Route::post('/trainee/{id}/pass', [LogisticsTraineeController::class, 'pass'])->name('trainee.pass');
    Route::post('/trainee/{id}/fail', [LogisticsTraineeController::class, 'fail'])->name('trainee.fail');
});

/*
|--------------------------------------------------------------------------
| Customer Relationship Management (CRM) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/crm')->name('crm.')->middleware(['auth', 'verified', 'module.access:CRM'])->group(function () {
    // Dashboard
    Route::get('/', [CrmDashboardController::class, 'index'])
        ->middleware('page.permission:dashboard,view')
        ->name('dashboard');

    // Leads
    Route::get('/lead', [LeadController::class, 'index'])
        ->middleware('page.permission:leads,view')
        ->name('lead');
    Route::post('/lead/store', [LeadController::class, 'store'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.store');
    Route::patch('/lead/{id}/status', [LeadController::class, 'updateStatus'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.status');
    Route::post('/lead/convert', [LeadController::class, 'convertToClient'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.convert');
    Route::post('/lead/{id}/note', [LeadController::class, 'addNote'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.add-note');
    Route::post('/lead/{id}/interview', [LeadController::class, 'scheduleInterview'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.schedule-interview');
    Route::post('/lead/{id}/file', [LeadController::class, 'uploadApprovalFile'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.upload-file');
    Route::post('/lead/{id}/accept', [LeadController::class, 'acceptLead'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.accept');
    Route::post('/lead/{id}/reject', [LeadController::class, 'rejectLead'])
        ->middleware('page.permission:leads,edit')
        ->name('lead.reject');

    // Interviews
    Route::get('/interview', [CrmInterviewController::class, 'index'])
        ->middleware('page.permission:interviews,view')
        ->name('interview.index');
    Route::post('/interview/{id}/schedule', [CrmInterviewController::class, 'schedule'])
        ->middleware('page.permission:interviews,edit')
        ->name('interview.schedule');
    Route::post('/interview/{id}/pass', [CrmInterviewController::class, 'pass'])
        ->middleware('page.permission:interviews,edit')
        ->name('interview.pass');
    Route::post('/interview/{id}/fail', [CrmInterviewController::class, 'fail'])
        ->middleware('page.permission:interviews,edit')
        ->name('interview.fail');
    Route::post('/interview/{id}/pass-to-other', [CrmInterviewController::class, 'passToOtherModule'])
        ->middleware('page.permission:interviews,edit')
        ->name('interview.pass-to-other');

    // Trainees
    Route::get('/trainee', [CrmTraineeController::class, 'index'])
        ->middleware('page.permission:trainees,view')
        ->name('trainee.index');
    Route::post('/trainee/{id}/grade', [CrmTraineeController::class, 'grade'])
        ->middleware('page.permission:trainees,edit')
        ->name('trainee.grade');
    Route::post('/trainee/{id}/pass', [CrmTraineeController::class, 'pass'])
        ->middleware('page.permission:trainees,edit')
        ->name('trainee.pass');
    Route::post('/trainee/{id}/fail', [CrmTraineeController::class, 'fail'])
        ->middleware('page.permission:trainees,edit')
        ->name('trainee.fail');

    // Approvals
    Route::get('/approval', [ApprovalController::class, 'index'])
        ->middleware('page.permission:approvals,view')
        ->name('approval.index');
    Route::post('/approval/{id}/note', [ApprovalController::class, 'addNote'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.note');
    Route::post('/approval/{id}/meeting', [ApprovalController::class, 'scheduleMeeting'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.meeting');
    Route::patch('/approval/meeting/{meetingId}/status', [ApprovalController::class, 'updateMeetingStatus'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.meeting.update');
    Route::post('/approval/{id}/accept', [ApprovalController::class, 'accept'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.accept');
    Route::post('/approval/{id}/reject', [ApprovalController::class, 'reject'])
        ->middleware('page.permission:approvals,edit')
        ->name('approval.reject');

    // Customer Profiles
    Route::get('/customerprofile', [CustomerProfileController::class, 'index'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.index');
    Route::get('/customerprofile/{id}', [CustomerProfileController::class, 'show'])
        ->middleware('page.permission:customer_profiles,view')
        ->name('customerprofile.show');

    // Investigation
    Route::get('/investigation', [InvestigationController::class, 'index'])
        ->middleware('page.permission:investigation,view')
        ->name('investigation.index');
    Route::post('/investigation/assign', [InvestigationController::class, 'assignStaff'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.assign');
    Route::post('/investigation/feedback', [InvestigationController::class, 'storeFeedback'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.feedback.store');
    Route::patch('/investigation/feedback/{id}/status', [InvestigationController::class, 'updateFeedbackStatus'])
        ->middleware('page.permission:investigation,edit')
        ->name('investigation.feedback.status');

    // Access Control (for CRM staff – managed by CEO)
    Route::get('/access', [CrmAccessController::class, 'index'])
        ->middleware('page.permission:access,view')
        ->name('access.index');
    Route::post('/access/update', [CrmAccessController::class, 'update'])
        ->middleware('page.permission:access,edit')
        ->name('access.update');
});

/*
|--------------------------------------------------------------------------
| E‑Commerce (ECO) Module
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/eco')->name('eco.')->middleware(['auth', 'verified', 'module.access:ECO'])->group(function () {
    // Dashboard
    Route::get('/', [EcoDashboardController::class, 'index'])->name('dashboard');
    // Store (product catalog)
    Route::get('/store', [EcoStoreController::class, 'index'])->name('store');
    // Inquiries & Conversations
    Route::get('/inquiries', [EcoInquiryController::class, 'index'])->name('inquiries');
    Route::get('/inquiries/{inquiry}', [EcoInquiryController::class, 'show'])->name('inquiry.show');
    Route::post('/inquiries/{inquiry}/message', [EcoInquiryController::class, 'sendMessage'])->name('inquiry.message');
    Route::post('/inquiries/{inquiry}/meeting', [EcoInquiryController::class, 'setMeeting'])->name('inquiry.meeting');
    Route::post('/inquiries/{inquiry}/quotation', [EcoInquiryController::class, 'issueQuotation'])->name('inquiry.quotation');
    Route::get('/clients/{client}/credit-check', [EcoInquiryController::class, 'creditCheck'])->name('credit.check');

    // NEW: Attachment actions for ECO
    Route::post('/attachment/{attachment}/create-recipe', [EcoInquiryController::class, 'createRecipeFromAttachment'])
        ->name('attachment.create-recipe');
    Route::post('/attachment/{attachment}/create-job-order', [EcoInquiryController::class, 'createJobOrderFromPO'])
        ->name('attachment.create-job-order');

    // Credit ledger
    Route::get('/credit', [EcoCreditController::class, 'index'])->name('credit');
    Route::post('/credit/approve/{order}', [EcoCreditController::class, 'approveCreditReview'])->name('credit.approve');
    Route::post('/credit/approve/{order}', [EcoCreditController::class, 'approveOrder'])->name('credit.approve');
    Route::post('/credit/reject/{order}', [EcoCreditController::class, 'rejectOrder'])->name('credit.reject');
    // Push to SCM / Order Management
    Route::get('/push', [EcoPushController::class, 'index'])->name('push');
    Route::post('/push/scm/{order}', [EcoPushController::class, 'pushToScm'])->name('push.scm');
    Route::post('/push/order-mgmt/{order}', [EcoPushController::class, 'pushToOrderMgmt'])->name('push.ordermgmt');
    // Access control (only CEO)
    Route::get('/access', [EcoAccessController::class, 'index'])->name('access');
    Route::post('/access/update', [EcoAccessController::class, 'update'])->name('access.update');

    // Inside ECO route group
    Route::get('/suppliers', [EcoSupplierController::class, 'index'])->name('suppliers');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])->name('supplier.conversation');
    Route::post('/suppliers/{supplier}/message', [EcoSupplierController::class, 'sendMessage'])->name('supplier.message');
    Route::post('/suppliers/{supplier}/meeting', [EcoSupplierController::class, 'setMeeting'])->name('supplier.meeting');
    Route::get('/suppliers/{supplier}/credit-check', [EcoSupplierController::class, 'creditCheck'])->name('supplier.credit-check');
    Route::post('/suppliers/{supplier}/request', [EcoSupplierController::class, 'sendRequest'])->name('supplier.request');
    Route::get('/suppliers/{supplier}/conversation', [EcoSupplierController::class, 'conversation'])->name('supplier.conversation');

    Route::post('/push/manual-po', [EcoPushController::class, 'manualStore'])->name('po.manual_store');
    Route::post('/push/manual-jo', [EcoPushController::class, 'manualJobOrder'])->name('jo.manual_store');
});

/*
|--------------------------------------------------------------------------
| Procurement Sub-System (PRO) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/pro')->name('pro.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::middleware(['module.access:PRO', 'position:manager'])->prefix('manager')->name('manager.')->group(function () {
        Route::get('/', [ProDashboardController::class, 'managerDashboard'])->name('dashboard');
        Route::get('/material-requests', [ProcurementController::class, 'materialRequests'])->name('material-requests');
        Route::post('/material-requests/rfq', [ProcurementController::class, 'createRFQ'])->name('rfq.store');
        Route::get('/supplier-quotations', [ProcurementController::class, 'supplierQuotations'])->name('supplier-quotations');
        Route::post('/quotations/{responseId}/accept', [ProcurementController::class, 'acceptQuotation'])->name('quotations.accept');
        Route::post('/quotations/{responseId}/decline', [ProcurementController::class, 'declineQuotation'])->name('quotations.decline');
        Route::get('/receipt', [ProcurementController::class, 'receipt'])->name('receipt');
        Route::post('/purchase-orders/{poId}/send', [ProcurementController::class, 'sendPurchaseOrder'])->name('purchase-orders.send');
        Route::get('/access', [ProAccessController::class, 'index'])->name('access.index')
            ->middleware('role:CEO');
        Route::post('/access/update', [ProAccessController::class, 'update'])->name('access.update')
            ->middleware('role:CEO');
    });
});

/*
|--------------------------------------------------------------------------
| Project Automation (PROJ) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/proj')->name('proj.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::get('/manager', [ProjDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:PROJ', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [ProjDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:PROJ', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| IT & Systems Admin (IT) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/it')->name('it.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/interview', [InterviewController::class, 'index'])->name('interview.index');
    Route::get('/trainee', [TraineeController::class, 'index'])->name('trainee.index');
    Route::get('/access', [AccessController::class, 'index'])->name('access.index');

    Route::get('/manager', [ItDashboardController::class, 'managerDashboard'])
        ->middleware(['module.access:IT', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [ItDashboardController::class, 'staffDashboard'])
        ->middleware(['module.access:IT', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| CEO Dashboard Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/ceo')->name('ceo.')->middleware(['auth', 'verified', 'role:CEO'])->group(function () {
    Route::get('/', [CeoDashboardController::class, 'index'])->name('dashboard');

    // Access Control
    Route::get('/access', [CeoAccessController::class, 'index'])->name('access');
    Route::get('/access/employee/{id}/personal-info', [CeoAccessController::class, 'getEmployeePersonalInfo'])->name('access.employeePersonalInfo');

    Route::post('/access/update-position', [CeoAccessController::class, 'updatePosition'])->name('access.updatePosition');
    Route::post('/access/update-modules', [CeoAccessController::class, 'updateModules'])->name('access.updateModules');
    Route::post('/access/update-staff-pages', [CeoAccessController::class, 'updateStaffPages'])->name('access.updateStaffPages');
    Route::post('/access/assign-staff-role', [CeoAccessController::class, 'assignStaffRole'])->name('access.assignStaffRole');
    Route::post('/access/update-profile-photo', [CeoAccessController::class, 'updateProfilePhoto'])->name('access.updateProfilePhoto');
    Route::get('/access/client-assignments/{staffId}', [CeoAccessController::class, 'getClientAssignments'])->name('access.clientAssignments');
    Route::post('/access/assign-clients', [CeoAccessController::class, 'updateClientAssignments'])->name('access.updateClientAssignments');

    // Geolocation Page View (GET)
    Route::get('/location', [GeolocationController::class, 'index'])->name('location.index');

    // Geolocation Data Sync (POST)
    Route::post('/user/location/sync', [GeolocationController::class, 'store'])->name('location.store');
});

/*
|--------------------------------------------------------------------------
| B2B Client Gateway Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest:client')->group(function () {
    Route::get('client/register', [ClientAuthController::class, 'create'])->name('client.register');
    Route::post('client/register', [ClientAuthController::class, 'store'])->name('client.register.store');
    Route::get('client/login', [ClientAuthController::class, 'showLogin'])->name('client.login');
    Route::post('client/login', [ClientAuthController::class, 'login'])->name('client.login.store');
});

Route::post('client/logout', [ClientAuthController::class, 'logout'])
    ->middleware('auth:client')
    ->name('client.logout');

/*
|--------------------------------------------------------------------------
| Protected Client B2B Portal Routes (Restructured)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:client')->prefix('partner')->name('client.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    // Products & Inquiries
    Route::get('/products', [ClientProductsController::class, 'index'])->name('products');
    Route::post('/products/{product}/inquire', [ClientProductsController::class, 'inquire'])->name('products.inquire');
    Route::post('/products/bulk-inquire', [ClientProductsController::class, 'bulkInquire'])->name('products.bulk-inquire');
    // Conversations
    Route::get('/conversations', [ClientConversationController::class, 'index'])->name('conversations');
    Route::get('/conversations/{inquiry}', [ClientConversationController::class, 'show'])->name('conversation.show');
    Route::post('/conversations/{inquiry}/message', [ClientConversationController::class, 'sendMessage'])->name('conversation.message');
    Route::post('/quotations/{quotation}/accept', [ClientConversationController::class, 'acceptQuotation'])->name('client.quotation.accept');
    Route::post('/quotations/{quotation}/reject', [ClientConversationController::class, 'rejectQuotation'])->name('client.quotation.reject');
    Route::get('quotations/{quotation}/download', [ClientConversationController::class, 'downloadQuotation'])
        ->name('client.quotation.download');
    // CORRECTED: send-po route name
    Route::post('/conversation/{inquiry}/send-po', [ClientConversationController::class, 'sendPO'])->name('conversation.send-po');

    // NEW: Attachment approval by client
    Route::post('/conversation/attachment/{attachment}/approve', [ClientConversationController::class, 'approveAttachment'])
        ->name('conversation.attachment.approve');

    // Orders & Invoices (legacy support)
    Route::get('/orders', [OrdersController::class, 'orders'])->name('orders');
    Route::post('/orders/{order}/accept', [OrdersController::class, 'acceptPurchaseOrder'])->name('orders.accept');
    Route::get('/invoices', [ClientInvoiceController::class, 'index'])->name('invoices');
    // Receiving (delivery confirmation)
    Route::get('/receiving', [ClientReceivingController::class, 'index'])->name('receiving');
    Route::post('/receiving/{order}/mark', [ClientReceivingController::class, 'markReceived'])->name('receiving.mark');
    // Profile
    Route::get('/profile', [ClientProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ClientProfileController::class, 'update'])->name('profile.update');
    // Support
    Route::get('/support', [ClientSupportController::class, 'index'])->name('support');
    Route::post('/support/complaint', [ClientSupportController::class, 'storeComplaint'])->name('support.complaint');

    // Legacy routes (preserved for backward compatibility)
    Route::post('/purchase-order', [ClientDashboardController::class, 'placeOrder'])->name('purchase-order.store');
    Route::post('/quotations/{quotation}/accept', [ClientConversationController::class, 'acceptQuotation'])->name('quotation.accept');
    Route::post('/quotations/{quotation}/reject', [ClientConversationController::class, 'rejectQuotation'])->name('quotation.reject');
});

/*
|--------------------------------------------------------------------------
| Vendor/Supplier Gateway Authentication Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest:supplier')->group(function () {
    Route::get('supplier/login', [SupplierAuthController::class, 'showLogin'])->name('supplier.login');
    Route::post('supplier/login', [SupplierAuthController::class, 'login'])->name('supplier.login.store');
    Route::get('supplier/register', [SupplierAuthController::class, 'create'])->name('supplier.register');
    Route::post('supplier/register', [SupplierAuthController::class, 'store'])->name('supplier.register.store');
});

Route::post('supplier/logout', [SupplierAuthController::class, 'logout'])
    ->middleware('auth:supplier')
    ->name('supplier.logout');

/*
|--------------------------------------------------------------------------
| Protected Supplier Portal Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:supplier')->prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/dashboard', [SupplierDashboardController::class, 'index'])->name('dashboard');
    Route::post('/rfq/{id}/respond', [SupplierDashboardController::class, 'submitQuotation'])->name('rfq.respond');
    Route::get('/orders', [SupplierDashboardController::class, 'purchaseOrders'])->name('orders');
    Route::post('/orders/{id}/status', [SupplierDashboardController::class, 'updateOrderStatus'])->name('orders.update_status');
    Route::post('/orders/{id}/invoice', [SupplierDashboardController::class, 'createInvoice'])->name('orders.invoice');
});

/*
|--------------------------------------------------------------------------
| Framework Generated Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';