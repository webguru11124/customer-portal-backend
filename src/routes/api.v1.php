<?php

use App\Http\Controllers\API\V1\AchTransactionSetupController;
use App\Http\Controllers\API\V1\Admin\OfficeController;
use App\Http\Controllers\API\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\V1\AppointmentController;
use App\Http\Controllers\API\V1\AutoPayController;
use App\Http\Controllers\API\V1\CreditCardTransactionSetupController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\DocumentController;
use App\Http\Controllers\API\V1\EmailCheckController;
use App\Http\Controllers\API\V1\EmailVerificationController;
use App\Http\Controllers\API\V1\PaymentController;
use App\Http\Controllers\API\V1\PaymentProfileController;
use App\Http\Controllers\API\V1\SpotController;
use App\Http\Controllers\API\V1\SubscriptionController;
use App\Http\Controllers\API\V1\TicketController;
use App\Http\Controllers\API\V1\TransactionSetupController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => 'v1', 'as' => 'api.', 'middleware' => 'aptive.validate_request_json'], static function () {
    Route::post('/emailcheck', [EmailCheckController::class, 'check'])
        ->name('emailcheck');
    Route::get('/flush', static function () {
        if (env('APP_DEBUG')) {
            return Cache::flush();
        }
        return 'OK';
    });

    Route::middleware([
        'aptive.magiclink_optional', 'auth0.authorize.optional','aptive.fusion_optional'
    ])->group(function () {
        Route::post('/resend-verification-email', [EmailVerificationController::class, 'resendVerificationEmail'])
             ->name('resend-verification-email');

        Route::middleware([
            'aptive.authorize',
        ])->group(function () {
            Route::get('/user/accounts', [UserController::class, 'accounts'])
                ->name('user.accounts');

            Route::get('/transaction-setup/{slug}', [TransactionSetupController::class, 'show'])
                ->name('transaction-setup.show');

            Route::post('/transaction-setup', [TransactionSetupController::class, 'create'])
                ->name('transaction-setup.create');

            Route::post('/transaction-setup/{slug}/credit-cards', [CreditCardTransactionSetupController::class, 'store'])
                ->name('transaction-setup.credit-card.store');

            Route::post('/transaction-setup/{tsId}/add-card-profile', [CreditCardTransactionSetupController::class, 'complete'])
                ->name('transaction-setup.credit-card.complete');

            Route::middleware([
                'aptive.valid_account_number',
                'aptive.customer_session',
            ])->group(function () {
                $accountNumberParameter = ['accountNumber', '^\d+$'];

                Route::get('/customer/{accountNumber}', [CustomerController::class, 'show'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.show');

                Route::post('/transaction-setup/{accountNumber}/ach', [AchTransactionSetupController::class, 'store'])
                    ->where(...$accountNumberParameter)
                    ->name('transaction-setup.ach.store');

                Route::get(
                    '/user/accounts/{accountNumber}/paymentprofiles',
                    [PaymentProfileController::class, 'getUserPaymentProfiles']
                )
                    ->where(...$accountNumberParameter)
                    ->name('user.accounts.paymentprofiles.get');

                Route::delete(
                    '/user/accounts/{accountNumber}/paymentprofiles/{paymentProfileId}',
                    [PaymentProfileController::class, 'deleteUserPaymentProfile']
                )
                    ->where(...$accountNumberParameter)
                    ->where('paymentProfileId', '[0-9]+')
                    ->name('user.accounts.paymentprofiles.delete');

                Route::get('/user/{accountNumber}/test', static fn (int $accountNumber) => sprintf('Account number: %d', $accountNumber))
                    ->where(...$accountNumberParameter)
                    ->name('user.accountNumber.test');

                Route::post('/customer/{accountNumber}/spots', [SpotController::class, 'search'])
                    ->name('customer.spots');

                Route::put('/customer/{accountNumber}/appointment', [AppointmentController::class, 'create'])
                    ->name('customer.appointments.create');

                Route::post('/customer/{accountNumber}/appointments', [AppointmentController::class, 'search'])
                    ->name('customer.appointments.search');

                Route::patch('/customer/{accountNumber}/appointment/{appointmentId}', [AppointmentController::class, 'update'])
                    ->name('customer.appointments.update');

                Route::get('/customer/{accountNumber}/appointments/history', [AppointmentController::class, 'showHistory'])
                    ->name('customer.appointments.history');

                Route::get('/customer/{accountNumber}/appointments/upcoming', [AppointmentController::class, 'showUpcoming'])
                    ->name('customer.appointments.upcoming');

                Route::get('/customer/{accountNumber}/appointment/{appointmentId}', [AppointmentController::class, 'find'])
                    ->name('customer.appointments.find');

                Route::delete(
                    '/user/accounts/{accountNumber}/appointments/{appointmentId}',
                    [AppointmentController::class, 'cancel']
                )
                    ->where(...$accountNumberParameter)
                    ->where('appointmentId', '[0-9]+')
                    ->name('user.accounts.appointments.cancel');

                Route::delete(
                    '/customer/{accountNumber}/appointments/{appointmentId}',
                    [AppointmentController::class, 'cancel']
                )
                    ->where(...$accountNumberParameter)
                    ->where('appointmentId', '[0-9]+')
                    ->name('customer.appointments.cancel');

                Route::get('/customer/{accountNumber}/autopay', [AutoPayController::class, 'getAutoPayData'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.autopay.get');

                Route::get('/customer/{accountNumber}/documents', [DocumentController::class, 'getCustomerDocuments'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.documents.get');

                Route::get('/customer/{accountNumber}/documents/{documentId}/download', [DocumentController::class, 'downloadCustomerDocument'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.documents.download');

                Route::post('/customer/{accountNumber}/communication-preferences', [CustomerController::class, 'updateCommunicationPreferences'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.communication-preferences');

                Route::get('/customer/{accountNumber}/invoices', [TicketController::class, 'getTickets'])
                    ->name('customer.invoices.get');

                Route::get('/customer/{accountNumber}/subscriptions', [SubscriptionController::class, 'getUserSubscriptions'])
                    ->name('customer.subscriptions.get');

                Route::post('/customer/{accountNumber}/subscription/createFrozen', [SubscriptionController::class, 'createFrozenSubscription'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.subscription.createFrozen');

                Route::patch(
                    '/customer/{accountNumber}/paymentprofiles/{paymentProfileId}',
                    [PaymentProfileController::class, 'updatePaymentProfile']
                )
                    ->where(...$accountNumberParameter)
                    ->where('paymentProfileId', '[0-9]+')
                    ->name('customer.paymentprofiles.update');

                Route::get('/customer/{accountNumber}/paymentprofiles', [PaymentProfileController::class, 'getPaymentProfiles'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.getpaymentprofiles');

                Route::post('/customer/{accountNumber}/paymentprofiles/ach', [PaymentProfileController::class, 'createAchPaymentProfile'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.paymentprofiles.ach.create');

                Route::post('/customer/{accountNumber}/paymentprofiles/credit-card', [PaymentProfileController::class, 'createCreditCardPaymentProfile'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.paymentprofiles.creditcard.create');

                Route::post('/customer/{accountNumber}/paymentprofiles/credit-card/{transactionSetupId}', [PaymentProfileController::class, 'completeCreditCardPaymentProfile'])
                    ->where(...$accountNumberParameter)
                    ->where('transactionSetupId', '^[A-Fa-f0-9-]+$')
                    ->name('customer.paymentprofiles.creditcard.complete');

                Route::post('/customer/{accountNumber}/payments', [PaymentController::class, 'createPayment'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.payments.add');

                Route::get('/customer/{accountNumber}/payments', [PaymentController::class, 'getPayments'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.payments.get');

                Route::get('/customer/{accountNumber}/payments/{paymentId}', [PaymentController::class, 'getPayment'])
                    ->where(...$accountNumberParameter)
                    ->where('paymentId', '[0-9]+')
                    ->name('customer.payments.getpayment');
            });
        });
    });
    Route::middleware(['key'])->prefix('/admin')->name('admin.')->group(function () {
        Route::get('/users', [AdminUserController::class, 'index'])
            ->name('users.list');
        Route::delete('/users/{id}', [AdminUserController::class, 'destroy'])
            ->name('users.delete');
        Route::get('/config/offices', [OfficeController::class, 'getIds'])
            ->name('config.offices');
        Route::get('/config/offices/{officeID}/pestroutescredentials', [OfficeController::class, 'getPestroutesCredentials'])
            ->name('config.offices.pestroutesCredentials');
    });
});
