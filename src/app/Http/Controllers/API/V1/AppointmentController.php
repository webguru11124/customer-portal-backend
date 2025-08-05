<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\CreateAppointmentAction;
use App\Actions\Appointment\FindAppointmentAction;
use App\Actions\Appointment\SearchAppointmentsAction;
use App\Actions\Appointment\ShowAppointmentsHistoryAction;
use App\Actions\Appointment\ShowUpcomingAppointmentsAction;
use App\Actions\Appointment\UpdateAppointmentAction;
use App\DTO\Appointment\SearchAppointmentsDTO;
use App\Enums\Resources;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAppointmentsRequest;
use App\Http\Requests\SearchAppointmentsRequest;
use App\Http\Requests\UpdateAppointmentRequest;
use App\Http\Responses\Appointment\AppointmentsHistoryResponse;
use App\Http\Responses\Appointment\FindAppointmentResponse;
use App\Http\Responses\Appointment\SearchAppointmentsResponse;
use App\Http\Responses\ErrorResponse;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\AppointmentService;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceCreatedResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceDeletedResponse;
use Aptive\Illuminate\Http\JsonApi\ResourceUpdatedResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class AppointmentController extends Controller
{
    public function __construct(
        public AppointmentService $appointmentService,
        public AccountService $accountService
    ) {
    }

    /**
     * @throws ValidationException
     */
    public function search(
        SearchAppointmentsRequest $request,
        SearchAppointmentsAction $searchAppointmentsAction,
        int $accountNumber
    ): JsonApiResponse {
        try {
            /** @var Account $account */
            $account = $request->user()->getAccountByAccountNumber($accountNumber);
            $status = $request->get('status');
            $searchAppointmentDTO = new SearchAppointmentsDTO(
                officeId: $account->office_id,
                accountNumber: [$account->account_number],
                dateStart: $request->get('date_start'),
                dateEnd: $request->get('date_end'),
                status: is_null($status) ? $status : (array) $status
            );

            $appointmentsCollection = ($searchAppointmentsAction)($searchAppointmentDTO);

            return SearchAppointmentsResponse::make($request, $appointmentsCollection);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    /**
     * @throws ValidationException
     */
    public function create(
        CreateAppointmentsRequest $request,
        CreateAppointmentAction $createAppointmentAction,
        int $accountNumber
    ): JsonApiResponse {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);
            $spotId = $request->get('spotId');
            $notes = $request->get('notes');

            return ResourceCreatedResponse::make(
                $request,
                Resources::APPOINTMENT->value,
                ($createAppointmentAction)($account, $spotId, $notes)
            );
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    public function update(
        UpdateAppointmentRequest $request,
        UpdateAppointmentAction $updateAppointmentAction,
        int $accountNumber,
        int $appointmentId
    ): JsonApiResponse {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);
            $spotId = $request->get('spotId');
            $notes = $request->get('notes');

            $result = ($updateAppointmentAction)($account, $appointmentId, $spotId, $notes);

            return ResourceUpdatedResponse::make($request, Resources::APPOINTMENT->value, $result);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }
    }

    public function cancel(
        Request $request,
        CancelAppointmentAction $cancelAppointmentAction,
        int $accountNumber,
        int $appointmentId
    ): Response {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);
            ($cancelAppointmentAction)($account, $appointmentId);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return ResourceDeletedResponse::make();
    }

    public function showHistory(
        Request $request,
        ShowAppointmentsHistoryAction $showAppointmentsHistoryAction,
        int $accountNumber
    ): Response {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            $searchResult = ($showAppointmentsHistoryAction)($account);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return AppointmentsHistoryResponse::make($request, $searchResult);
    }

    public function showUpcoming(
        Request $request,
        ShowUpcomingAppointmentsAction $showUpcomingAppointmentsAction,
        int $accountNumber
    ): Response {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            $searchResult = ($showUpcomingAppointmentsAction)($account, (int) $request->get('limit'));
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return SearchAppointmentsResponse::make($request, $searchResult);
    }

    public function find(
        Request $request,
        FindAppointmentAction $findAppointmentAction,
        int $accountNumber,
        int $appointmentId
    ): Response {
        try {
            $account = $request->user()->getAccountByAccountNumber($accountNumber);

            $result = ($findAppointmentAction)($account, $appointmentId);
        } catch (Throwable $exception) {
            return ErrorResponse::fromException($request, $exception);
        }

        return FindAppointmentResponse::make($request, $result);
    }
}
