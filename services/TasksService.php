<?php

namespace Grocy\Services;

use LessQL\Result;
use Exception;

class TasksService extends BaseService
{
    public function getCurrent(): Result
    {
        $users = $this->getUsersService()->getUsersAsDto();
        $categories = $this->getDatabase()->task_categories()->where('active = 1');

        $tasks = $this->getDatabase()->tasks_current();
        foreach ($tasks as $task) {
            if (!empty($task->assigned_to_user_id)) {
                $task->assigned_to_user = findObjectInArrayByPropertyValue($users, 'id', $task->assigned_to_user_id);
            } else {
                $task->assigned_to_user = null;
            }

            if (!empty($task->category_id)) {
                $task->category = findObjectInArrayByPropertyValue($categories, 'id', $task->category_id);
            } else {
                $task->category = null;
            }
        }

        return $tasks;
    }

    public function markTaskAsCompleted($taskId, $doneTime): bool
    {
        if (!$this->taskExists($taskId)) {
            throw new Exception('Task does not exist');
        }

        $taskRow = $this->getDatabase()->tasks()->where('id = :1', $taskId)->fetch();
        $taskRow->update([
            'done' => 1,
            'done_timestamp' => $doneTime
        ]);

        return true;
    }

    public function undoTask($taskId): bool
    {
        if (!$this->taskExists($taskId)) {
            throw new Exception('Task does not exist');
        }

        $taskRow = $this->getDatabase()->tasks()->where('id = :1', $taskId)->fetch();
        $taskRow->update([
            'done' => 0,
            'done_timestamp' => null
        ]);

        return true;
    }

    private function taskExists($taskId): bool
    {
        $taskRow = $this->getDatabase()->tasks()->where('id = :1', $taskId)->fetch();
        return $taskRow !== null;
    }
}
