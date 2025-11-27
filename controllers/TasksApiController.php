<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TasksApiController extends BaseApiController
{
    public function current(Request $request, Response $response, array $args)
    {
        return $this->filteredApiResponse($response, $this->getTasksService()->getCurrent(), $request->getQueryParams());
    }

    public function markTaskAsCompleted(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_TASKS_MARK_COMPLETED);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            $doneTime = date('Y-m-d H:i:s');

            if (array_key_exists('done_time', $requestBody) && isIsoDateTime($requestBody['done_time'])) {
                $doneTime = $requestBody['done_time'];
            }

            $this->getTasksService()->markTaskAsCompleted($args['taskId'], $doneTime);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function undoTask(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_TASKS_UNDO_EXECUTION);

        try {
            $this->getTasksService()->undoTask($args['taskId']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }
}
