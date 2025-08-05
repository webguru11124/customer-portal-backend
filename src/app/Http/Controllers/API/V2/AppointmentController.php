<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V2;

use App\Actions\Appointment\CreateAppointmentInFlexIVRAction;
use App\Actions\Appointment\RescheduleAppointmentInFlexIVRAction;
use App\Enums\FlexIVR\Window;
use App\Exceptions\Account\AccountNotFoundException;
use App\Exceptions\Appointment\AppointmentCanNotBeCreatedException;
use App\Exceptions\Appointment\AppointmentCanNotBeRescheduledException;
use App\Exceptions\Appointment\AppointmentSpotAlreadyUsedException;
use App\Exceptions\Appointment\CannotCreateAppointmentException;
use App\Exceptions\Appointment\CannotGetCurrentAppointment;
use App\Exceptions\Appointment\CannotResolveAppointmentSubscriptionException;
use App\Exceptions\Entity\EntityNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\V2\CreateAppointmentsRequest;
use App\Http\Requests\V2\RescheduleAppointmentsRequest;
use App\Http\Responses\Appointment\FindAppointmentResponse;
use App\Http\Responses\ErrorResponse;
use Aptive\Component\Http\Exceptions\InternalServerErrorHttpException;
use Aptive\Component\Http\Exceptions\NotFoundHttpException;
use Aptive\Component\Http\HttpStatus;
use Aptive\Component\JsonApi\Exceptions\ValidationException;
use Aptive\Illuminate\Http\JsonApi\JsonApiResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class AppointmentController extends Controller
{
    /**
     * @throws ValidationException
     * @throws NotFoundHttpException
     * @throws InternalServerErrorHttpException
     */
    public function create(
        CreateAppointmentInFlexIVRAction $action,
        int $accountNumber,
        CreateAppointmentsRequest $request
    ): JsonApiResponse {
        try {
            $appointment = $action(
                accountNumber: $accountNumber,
                spotId: (int) $request->validated('spot_id'),
                window: Window::from($request->validated('window')),
                isAroSpot: (bool) $request->validated('is_aro_spot'),
                notes: $request->get('notes'),
            );

            return FindAppointmentResponse::make($request, $appointment)->setStatusCode(HttpStatus::CREATED);
        } catch (AppointmentSpotAlreadyUsedException $e) {
            throw $e;
        } catch (CannotResolveAppointmentSubscriptionException $e) {
            throw new InternalServerErrorHttpException(previous: $e);
        } catch (AccountNotFoundException|EntityNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        } catch (CannotCreateAppointmentException $e) {
            return ErrorResponse::fromException($request, $e, ResponseAlias::HTTP_CONFLICT);
        }
    }

    /**
     * @throws ValidationException
     * @throws NotFoundHttpException
     * @throws InternalServerErrorHttpException
     */
    public function reschedule(
        RescheduleAppointmentInFlexIVRAction $action,
        int $accountNumber,
        int $appointmentId,
        RescheduleAppointmentsRequest $request
    ): JsonApiResponse {
        try {
            $appointment = $action(
                accountNumber: $accountNumber,
                appointmentId: $appointmentId,
                spotId: (int) $request->validated('spot_id'),
                window: Window::from($request->validated('window')),
                isAroSpot: (bool) $request->validated('is_aro_spot'),
                notes: $request->get('notes'),
            );

            return FindAppointmentResponse::make($request, $appointment)->setStatusCode(HttpStatus::OK);
        } catch (AppointmentSpotAlreadyUsedException $e) {
            throw $e;
        } catch (AppointmentCanNotBeCreatedException|CannotGetCurrentAppointment $e) {
            throw new InternalServerErrorHttpException(previous: $e);
        } catch (AccountNotFoundException|EntityNotFoundException $e) {
            throw new NotFoundHttpException(previous: $e);
        } catch (AppointmentCanNotBeRescheduledException $e) {
            throw new ValidationException($e->getMessage(), previous: $e);
        }
    }
}
