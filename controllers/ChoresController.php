<?php

namespace Grocy\Controllers;

use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ChoresController extends BaseController
{
    use GrocycodeTrait;

    public function choreEditForm(Request $request, Response $response, array $args)
    {
        $usersService = $this->getUsersService();
        $users = $usersService->getUsersAsDto();

        if ($args['choreId'] == 'new') {
            return $this->renderPage($response, 'choreform', [
                'periodTypes' => getClassConstants(\Grocy\Services\ChoresService::class, 'CHORE_PERIOD_TYPE_'),
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('chores'),
                'assignmentTypes' => getClassConstants(\Grocy\Services\ChoresService::class, 'CHORE_ASSIGNMENT_TYPE_'),
                'users' => $users,
                'products' => $this->getDatabase()->products()->orderBy('name', 'COLLATE NOCASE')
            ]);
        } else {
            return $this->renderPage($response, 'choreform', [
                'chore' => $this->getDatabase()->chores($args['choreId']),
                'periodTypes' => getClassConstants(\Grocy\Services\ChoresService::class, 'CHORE_PERIOD_TYPE_'),
                'mode' => 'edit',
                'userfields' => $this->getUserfieldsService()->getFields('chores'),
                'assignmentTypes' => getClassConstants(\Grocy\Services\ChoresService::class, 'CHORE_ASSIGNMENT_TYPE_'),
                'users' => $users,
                'products' => $this->getDatabase()->products()->orderBy('name', 'COLLATE NOCASE')
            ]);
        }
    }

    public function choresList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $chores = $this->getDatabase()->chores()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $chores = $this->getDatabase()->chores()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'chores', [
            'chores' => $chores,
            'userfields' => $this->getUserfieldsService()->getFields('chores'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('chores')
        ]);
    }

    public function choresSettings(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'choressettings');
    }

    public function journal(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['months']) && filter_var($request->getQueryParams()['months'], FILTER_VALIDATE_INT) !== false) {
            $months = $request->getQueryParams()['months'];
            $where = sprintf("tracked_time > DATE(DATE('now', 'localtime'), '-%s months')", $months);
        } else {
            // Default 1 year
            $where = "tracked_time > DATE(DATE('now', 'localtime'), '-12 months')";
        }

        if (isset($request->getQueryParams()['chore']) && filter_var($request->getQueryParams()['chore'], FILTER_VALIDATE_INT) !== false) {
            $choreId = $request->getQueryParams()['chore'];
            $where .= ' AND chore_id = ' . $choreId;
        }

        return $this->renderPage($response, 'choresjournal', [
            'choresLog' => $this->getDatabase()->chores_log()->where($where)->orderBy('tracked_time', 'DESC'),
            'chores' => $this->getDatabase()->chores()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'users' => $this->getDatabase()->users()->orderBy('username'),
            'userfields' => $this->getUserfieldsService()->getFields('chores_log'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('chores_log')
        ]);
    }

    public function overview(Request $request, Response $response, array $args)
    {
        $usersService = $this->getUsersService();
        $nextXDays = $usersService->getUserSettings(GROCY_USER_ID)['chores_due_soon_days'];

        $chores = $this->getDatabase()->chores()->orderBy('name', 'COLLATE NOCASE');
        $currentChores = $this->getChoresService()->getCurrent();
        foreach ($currentChores as $currentChore) {
            if (!empty($currentChore->next_estimated_execution_time)) {
                if ($currentChore->next_estimated_execution_time < date('Y-m-d H:i:s')) {
                    $currentChore->due_type = 'overdue';
                } elseif ($currentChore->next_estimated_execution_time <= date('Y-m-d 23:59:59')) {
                    $currentChore->due_type = 'duetoday';
                } elseif ($nextXDays > 0 && $currentChore->next_estimated_execution_time <= date('Y-m-d H:i:s', strtotime('+' . $nextXDays . ' days'))) {
                    $currentChore->due_type = 'duesoon';
                }
            }
        }

        return $this->renderPage($response, 'choresoverview', [
            'chores' => $chores,
            'currentChores' => $currentChores,
            'nextXDays' => $nextXDays,
            'userfields' => $this->getUserfieldsService()->getFields('chores'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('chores'),
            'users' => $usersService->getUsersAsDto()
        ]);
    }

    public function trackChoreExecution(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'choretracking', [
            'chores' => $this->getDatabase()->chores()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'users' => $this->getDatabase()->users()->orderBy('username'),
            'userfields' => $this->getUserfieldsService()->getFields('chores_log'),
        ]);
    }

    public function choreGrocycodeImage(Request $request, Response $response, array $args)
    {
        $gc = new Grocycode(Grocycode::CHORE, $args['choreId']);
        return $this->serveGrocycodeImage($request, $response, $gc);
    }
}
