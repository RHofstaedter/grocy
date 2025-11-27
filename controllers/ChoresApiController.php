<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Helpers\WebhookRunner;
use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class ChoresApiController extends BaseApiController
{
    public function calculateNextExecutionAssignments(Request $request, Response $response, array $args)
    {
        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $choreId = null;

            if (array_key_exists('chore_id', $requestBody) && !empty($requestBody['chore_id']) && is_numeric($requestBody['chore_id'])) {
                $choreId = intval($requestBody['chore_id']);
            }

            if ($choreId === null) {
                $chores = $this->getDatabase()->chores();
                foreach ($chores as $chore) {
                    $this->getChoresService()->calculateNextExecutionAssignment($chore->id);
                }
            } else {
                $this->getChoresService()->calculateNextExecutionAssignment($choreId);
            }

            return $this->emptyApiResponse($response);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function choreDetails(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getChoresService()->getChoreDetails($args['choreId']));
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function current(Request $request, Response $response, array $args)
    {
        return $this->filteredApiResponse($response, $this->getChoresService()->getCurrent(), $request->getQueryParams());
    }

    public function trackChoreExecution(Request $request, Response $response, array $args)
    {
        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            User::checkPermission($request, User::PERMISSION_CHORE_TRACK_EXECUTION);

            $trackedTime = date('Y-m-d H:i:s');
            if (array_key_exists('tracked_time', $requestBody) && (isIsoDateTime($requestBody['tracked_time']) || isIsoDate($requestBody['tracked_time']))) {
                $trackedTime = $requestBody['tracked_time'];
            }

            $skipped = false;
            if (array_key_exists('skipped', $requestBody) && filter_var($requestBody['skipped'], FILTER_VALIDATE_BOOLEAN) !== false) {
                $skipped = $requestBody['skipped'];
            }

            $doneBy = GROCY_USER_ID;
            if (array_key_exists('done_by', $requestBody) && !empty($requestBody['done_by'])) {
                $doneBy = $requestBody['done_by'];
            }

            if ($doneBy != GROCY_USER_ID) {
                User::checkPermission($request, User::PERMISSION_CHORE_TRACK_EXECUTION);
            }

            $choreExecutionId = $this->getChoresService()->trackChore($args['choreId'], $trackedTime, $doneBy, $skipped);
            return $this->apiResponse($response, $this->getDatabase()->chores_log($choreExecutionId));
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function undoChoreExecution(Request $request, Response $response, array $args)
    {
        try {
            User::checkPermission($request, User::PERMISSION_CHORE_UNDO_EXECUTION);

            $this->apiResponse($response, $this->getChoresService()->undoChoreExecution($args['executionId']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function chorePrintLabel(Request $request, Response $response, array $args)
    {
        try {
            $choreDetails = (object)$this->getChoresService()->getChoreDetails($args['choreId']);

            $webhookData = array_merge([
                'chore' => $choreDetails->chore->name,
                'grocycode' => (string)(new Grocycode(Grocycode::CHORE, $args['choreId'])),
                'details' => $choreDetails,
            ], GROCY_LABEL_PRINTER_PARAMS);

            if (GROCY_LABEL_PRINTER_RUN_SERVER) {
                (new WebhookRunner())->run(GROCY_LABEL_PRINTER_WEBHOOK, $webhookData, GROCY_LABEL_PRINTER_HOOK_JSON);
            }

            return $this->apiResponse($response, $webhookData);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function mergeChores(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_MASTER_DATA_EDIT);

        try {
            if (filter_var($args['choreIdToKeep'], FILTER_VALIDATE_INT) === false || filter_var($args['choreIdToRemove'], FILTER_VALIDATE_INT) === false) {
                throw new Exception('Provided {choreIdToKeep} or {choreIdToRemove} is not a valid integer');
            }

            $this->apiResponse($response, $this->getChoresService()->mergeChores($args['choreIdToKeep'], $args['choreIdToRemove']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }
}
