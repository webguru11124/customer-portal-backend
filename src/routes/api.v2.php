<?php

declare(strict_types=1);

use App\Http\Controllers\API\V1\Admin\OfficeController;
use App\Http\Controllers\API\V1\Admin\UserController as AdminUserController;
use App\Http\Controllers\API\V1\AppointmentController as AppointmentControllerV1;
use App\Http\Controllers\API\V2\AutoPayController;
use App\Http\Controllers\API\V1\EmailCheckController;
use App\Http\Controllers\API\V1\EmailVerificationController;
use App\Http\Controllers\API\V1\TicketController;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Controllers\API\V2\AppointmentController as AppointmentControllerV2;
use App\Http\Controllers\API\V2\CustomerController;
use App\Http\Controllers\API\V2\DocumentController;
use App\Http\Controllers\API\V2\Admin\MagicLinkController;
use App\Http\Controllers\API\V2\MagicJWTController;
use App\Http\Controllers\API\V2\PaymentController;
use App\Http\Controllers\API\V2\PaymentProfileController;
use App\Http\Controllers\API\V2\SpotController as SpotControllerV2;
use App\Http\Controllers\API\V2\SubscriptionController;
use App\Http\Controllers\API\V2\ProductController;
use App\Http\Controllers\API\V2\UpgradeController;
use App\Http\Controllers\API\V2\PaymentService\AuthTokenController;
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

Route::group(['prefix' => 'v2', 'as' => 'api.v2.', 'middleware' => 'aptive.validate_request_json'], static function () {
    Route::get('/emailcheck', [EmailCheckController::class, 'check'])
        ->name('emailcheck');
    Route::middleware([
        'aptive.magiclink'
    ])->group(function () {
        Route::post('/magicjwt', [MagicJWTController::class, 'getToken'])
            ->name('get-jwt-token');
    });
    Route::middleware([
        'aptive.magiclink_optional', 'auth0.authorize.optional','aptive.fusion_optional'
    ])->group(function () {
        Route::post('/resend-verification-email', [EmailVerificationController::class, 'resendVerificationEmail'])
             ->name('resend-verification-email');

        Route::middleware([
            'aptive.authorize',
        ])->group(function () {
            Route::post('/payment-service/auth-token', [AuthTokenController::class, 'retrieveToken'])
            ->name('payment-service.auth-token');

            Route::get('/user/accounts', [UserController::class, 'accounts'])
                ->name('user.accounts');

            Route::get('/customer/accounts', [UserController::class, 'accounts'])
                ->name('customer.accounts');

            Route::middleware([
                'aptive.valid_account_number',
                'aptive.customer_session',
            ])->group(function () {
                $accountNumberParameter = ['accountNumber', '^\d+$'];

                Route::get('/customer/{accountNumber}', [CustomerController::class, 'show'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.show');

                Route::get('/customer/{accountNumber}/data', [CustomerController::class, 'getCustomerData'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.data');

                Route::get('/customer/{accountNumber}/autopay', [AutoPayController::class, 'getAutoPayData'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.autopay.get');

                Route::post('/customer/{accountNumber}/communication-preferences', [CustomerController::class, 'updateCommunicationPreferences'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.communication-preferences');

                Route::get('/user/{accountNumber}/test', static fn (int $accountNumber) => sprintf('Account number: %d', $accountNumber))
                    ->where(...$accountNumberParameter)
                    ->name('user.accountNumber.test');

                Route::get('/customer/{accountNumber}/test', static fn (int $accountNumber) => sprintf('Account number: %d', $accountNumber))
                    ->where(...$accountNumberParameter)
                    ->name('customer.accountNumber.test');

                Route::get('/customer/{accountNumber}/appointments', [AppointmentControllerV1::class, 'search'])
                     ->name('customer.appointments.search');

                Route::post('/customer/{accountNumber}/appointments', [AppointmentControllerV2::class, 'create'])
                    ->name('customer.appointments.create.flex');

                Route::put('/customer/{accountNumber}/appointments', [AppointmentControllerV1::class, 'create'])
                    ->name('customer.appointments.create');

                Route::get('/customer/{accountNumber}/appointments/history', [AppointmentControllerV1::class, 'showHistory'])
                    ->name('customer.appointments.history');

                Route::get('/customer/{accountNumber}/appointments/upcoming', [AppointmentControllerV1::class, 'showUpcoming'])
                    ->name('customer.appointments.upcoming');

                Route::get('/customer/{accountNumber}/appointments/{appointmentId}', [AppointmentControllerV1::class, 'find'])
                    ->name('customer.appointments.find');

                Route::patch('/customer/{accountNumber}/appointments/{appointmentId}', [AppointmentControllerV2::class, 'reschedule'])
                    ->name('customer.appointments.reschedule');

                Route::delete(
                    '/customer/{accountNumber}/appointments/{appointmentId}',
                    [AppointmentControllerV1::class, 'cancel']
                )
                    ->where(...$accountNumberParameter)
                    ->where('appointmentId', '[0-9]+')
                    ->name('customer.appointments.cancel');

                Route::get('/customer/{accountNumber}/documents', [DocumentController::class, 'getCustomerDocuments'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.documents.get');

                Route::get('/customer/{accountNumber}/documents/{documentId}/download', [DocumentController::class, 'downloadCustomerDocument'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.documents.download');

                Route::get('/customer/{accountNumber}/invoices', [TicketController::class, 'getTickets'])
                    ->name('customer.invoices.get');

                Route::get('/customer/{accountNumber}/subscriptions', [SubscriptionController::class, 'getUserSubscriptions'])
                    ->name('customer.subscriptions.get');

                Route::post('/customer/{accountNumber}/subscription/createFrozen', [SubscriptionController::class, 'createFrozenSubscription'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.subscription.createFrozen');

                Route::patch('/customer/{accountNumber}/subscription/{subscriptionId}/activate', [SubscriptionController::class, 'activateSubscription'])
                    ->where(...$accountNumberParameter)
                    ->where('subscriptionId', '[0-9]+')
                    ->name('customer.subscription.activateSubscription');

                Route::get(
                    '/customer/{accountNumber}/paymentprofiles',
                    [PaymentProfileController::class, 'getAptiveUserPaymentProfiles']
                )
                     ->where(...$accountNumberParameter)
                     ->name('customer.paymentprofiles.get');

                Route::patch(
                    '/customer/{accountNumber}/paymentprofiles/{paymentProfileId}',
                    [PaymentProfileController::class, 'updatePaymentProfile']
                )
                    ->where(...$accountNumberParameter)
                    ->where('paymentProfileId', '[0-9]+')
                    ->name('customer.paymentprofiles.update');

                Route::delete(
                    '/customer/{accountNumber}/paymentprofiles/{paymentProfileId}',
                    [PaymentProfileController::class, 'deleteAptivePaymentProfile']
                )
                     ->where(...$accountNumberParameter)
                     ->where('paymentProfileId', '^[A-Fa-f0-9-]+$')
                     ->name('customer.paymentprofiles.delete');

                Route::post('/customer/{accountNumber}/paymentprofiles/ach', [PaymentProfileController::class, 'createAchPaymentProfileV2'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.paymentprofiles.ach.create');

                Route::post('/customer/{accountNumber}/paymentprofiles/credit-card', [PaymentProfileController::class, 'createAptiveCreditCardPaymentProfile'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.paymentprofiles.creditcard.create');

                Route::post('/customer/{accountNumber}/paymentprofiles/credit-card/{transactionSetupId}', [PaymentProfileController::class, 'completeCreditCardPaymentProfile'])
                    ->where(...$accountNumberParameter)
                    ->where('transactionSetupId', '^[A-Fa-f0-9-]+$')
                    ->name('customer.paymentprofiles.creditcard.complete');

                Route::post('/customer/{accountNumber}/payments', [PaymentController::class, 'createAptivePayment'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.payments.add');

                Route::get('/customer/{accountNumber}/payments', [PaymentController::class, 'getPayments'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.payments.get');

                Route::get('/customer/{accountNumber}/payments/{paymentId}', [PaymentController::class, 'getPayment'])
                    ->where(...$accountNumberParameter)
                    ->where('paymentId', '[0-9]+')
                    ->name('customer.payments.getpayment');

                Route::get('/customer/{accountNumber}/spots', [SpotControllerV2::class, 'search'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.spots.get');

                Route::get('/customer/{accountNumber}/upgrades', [UpgradeController::class, 'get'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.upgrades.get');

                Route::get('/customer/{accountNumber}/products', [ProductController::class, 'get'])
                    ->where(...$accountNumberParameter)
                    ->name('customer.products.get');
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
        Route::post('/magiclink', [MagicLinkController::class, 'getLink'])
            ->name('magiclink');
    });
});
