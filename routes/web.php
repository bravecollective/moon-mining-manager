<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactFormController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ExtractionsController;
use App\Http\Controllers\MinerController;
use App\Http\Controllers\MoonAdminController;
use App\Http\Controllers\MoonController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RenterController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\TimerController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Login pages.
Route::get('/login', function () {
    return view('login');
})->name('login');
Route::get('/admin', function () {
    return view('admin-login');
})->name('admin-login');

// Public list of upcoming mining timers.
Route::middleware(['login'])->prefix('timers')->group(function () {
    Route::get('/', [TimerController::class, 'home']);
    Route::post('/claim/{claim}/{refinery}', [TimerController::class, 'claim']);
    Route::get('/clear/{claim}/{refinery}', [TimerController::class, 'clear']);
});

// Search interface.
Route::get('/search', [SearchController::class, 'search']);

// Admin interface home.
Route::get('/', [AppController::class, 'home'])->middleware('admin');

// Access management.
Route::middleware(['admin'])->prefix('access')->group(function () {
    Route::get('/', [AppController::class, 'showAuthorisedUsers']);
    //Route::get('/new', [AppController::class, 'showUserAccessHistory']);
    Route::post('/admin/{id}', [AppController::class, 'makeUserAdmin']);
    Route::post('/whitelist/{id}', [AppController::class, 'whitelistUser']);
    Route::post('/blacklist/{id}', [AppController::class, 'blacklistUser']);
    Route::post('/toggle-form-mail/{id}', [AppController::class, 'toggleFormMail']);
});

// Reports.
Route::middleware(['admin'])->prefix('reports')->group(function () {
    Route::get('/{year?}/{month?}', [ReportsController::class, 'main'])->where([
        'year' => '[0-9]{4}',
        'month' => '[0-9]{2}'
    ]);
    Route::get('/fix', [ReportsController::class, 'fix']);
    Route::get('/regenerate', [ReportsController::class, 'regenerate']);
});

// Miner reporting.
Route::middleware(['admin'])->prefix('miners')->group(function () {
    Route::get('/', [MinerController::class, 'showMiners']);
    Route::get('/{id}', [MinerController::class, 'showMinerDetails']);
});

// Renter management.
Route::middleware(['admin'])->prefix('renters')->group(function () {
    Route::get('/', [RenterController::class, 'showRenters']);
    Route::get('/expired', [RenterController::class, 'showExpiredRenters']);
    Route::get('/new', [RenterController::class, 'addNewRenter']);
    Route::post('/new', [RenterController::class, 'saveNewRenter']);
    Route::get('/{id}', [RenterController::class, 'editRenter']);
    Route::post('/{id}', [RenterController::class, 'updateRenter']);
    Route::get('/refinery/{id}', [RenterController::class, 'refineryDetails']);
    Route::get('/character/{id}', [RenterController::class, 'renterDetails']);
});

// Public list of available moons.
Route::middleware(['login'])->prefix('moons')->group(function () {
    Route::get('/', [MoonController::class, 'index']);
});

// Contact form
Route::middleware(['login'])->prefix('contact-form')->group(function () {
    Route::get('/', [ContactFormController::class, 'index']);
    Route::post('/', [ContactFormController::class, 'send']);
});

// Moon composition importer.
Route::middleware(['admin'])->prefix('moon-admin')->group(function () {
    Route::get('/list', [MoonAdminController::class, 'index']);
    Route::post('/update-status', [MoonAdminController::class, 'updateStatus']);
    Route::get('/', [MoonAdminController::class, 'admin']);
    Route::post('/import', [MoonAdminController::class, 'import']);
    Route::post('/import_survey_data', [MoonAdminController::class, 'importSurveyData']);
    Route::get('/export', [MoonAdminController::class, 'export']);
    Route::get('/calculate', [MoonAdminController::class, 'calculate']);
});

Route::middleware(['admin'])->prefix('extractions')->group(function () {
    Route::get('/', [ExtractionsController::class, 'index']);
});

// Payment management.
Route::middleware(['admin'])->prefix('payment')->group(function () {
    Route::get('/', [PaymentController::class, 'listManualPayments']);
    Route::get('/new', [PaymentController::class, 'addNewPayment']);
    Route::post('/new', [PaymentController::class, 'insertNewPayment']);
});

// Tax management.
Route::middleware(['admin'])->prefix('taxes')->group(function () {
    Route::get('/', [TaxController::class, 'showTaxRates']);
    Route::get('/history', [TaxController::class, 'showHistory']);
    Route::post('/update_value/{id}', [TaxController::class, 'updateValue']);
    Route::post('/update_rate/{id}', [TaxController::class, 'updateTaxRate']);
    Route::post('/update_master_rate', [TaxController::class, 'updateMasterTaxRate']);
    //Route::get('/load', [TaxController::class, 'loadInitialTaxRates']);
});

// Email template management.
Route::middleware(['admin'])->prefix('emails')->group(function () {
    Route::get('/', [EmailController::class, 'showEmails']);
    Route::post('/update', [EmailController::class, 'updateEmails']);
});

// Handle EVE SSO requests and callbacks.
Route::get('/sso', [AuthController::class, 'redirectToProvider']);
Route::get('/admin-sso', [AuthController::class, 'redirectToProviderForAdmin']);
Route::get('/callback', [AuthController::class, 'handleProviderCallback']);

// Logout.
Route::get('/logout', [AppController::class, 'logout']);
