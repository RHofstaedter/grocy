<?php

namespace Grocy\Controllers;

use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BatteriesController extends BaseController
{
    use GrocycodeTrait;

    public function batteriesList(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['include_disabled'])) {
            $batteries = $this->getDatabase()->batteries()->orderBy('name', 'COLLATE NOCASE');
        } else {
            $batteries = $this->getDatabase()->batteries()->where('active = 1')->orderBy('name', 'COLLATE NOCASE');
        }

        return $this->renderPage($response, 'batteries', [
            'batteries' => $batteries,
            'userfields' => $this->getUserfieldsService()->getFields('batteries'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('batteries')
        ]);
    }

    public function batteriesSettings(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'batteriessettings');
    }

    public function batteryEditForm(Request $request, Response $response, array $args)
    {
        if ($args['batteryId'] == 'new') {
            return $this->renderPage($response, 'batteryform', [
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('batteries')
            ]);
        } else {
            return $this->renderPage($response, 'batteryform', [
                'battery' => $this->getDatabase()->batteries($args['batteryId']),
                'mode' => 'edit',
                'userfields' => $this->getUserfieldsService()->getFields('batteries')
            ]);
        }
    }

    public function journal(Request $request, Response $response, array $args)
    {
        if (isset($request->getQueryParams()['months']) && filter_var($request->getQueryParams()['months'], FILTER_VALIDATE_INT) !== false) {
            $months = $request->getQueryParams()['months'];
            $where = sprintf("tracked_time > DATE(DATE('now', 'localtime'), '-%s months')", $months);
        } else {
            // Default 2 years
            $where = "tracked_time > DATE(DATE('now', 'localtime'), '-24 months')";
        }

        if (isset($request->getQueryParams()['battery']) && filter_var($request->getQueryParams()['battery'], FILTER_VALIDATE_INT) !== false) {
            $batteryId = $request->getQueryParams()['battery'];
            $where .= ' AND battery_id = ' . $batteryId;
        }

        return $this->renderPage($response, 'batteriesjournal', [
            'chargeCycles' => $this->getDatabase()->battery_charge_cycles()->where($where)->orderBy('tracked_time', 'DESC'),
            'batteries' => $this->getDatabase()->batteries()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('battery_charge_cycles'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('battery_charge_cycles')
        ]);
    }

    public function overview(Request $request, Response $response, array $args)
    {
        $usersService = $this->getUsersService();
        $nextXDays = $usersService->getUserSettings(GROCY_USER_ID)['batteries_due_soon_days'];

        $batteries = $this->getDatabase()->batteries()->where('active = 1');
        $currentBatteries = $this->getBatteriesService()->getCurrent();
        foreach ($currentBatteries as $currentBattery) {
            if (findObjectInArrayByPropertyValue($batteries, 'id', $currentBattery->battery_id)->charge_interval_days > 0) {
                if ($currentBattery->next_estimated_charge_time < date('Y-m-d H:i:s')) {
                    $currentBattery->due_type = 'overdue';
                } elseif ($currentBattery->next_estimated_charge_time <= date('Y-m-d 23:59:59')) {
                    $currentBattery->due_type = 'duetoday';
                } elseif ($nextXDays > 0 && $currentBattery->next_estimated_charge_time <= date('Y-m-d H:i:s', strtotime('+' . $nextXDays . ' days'))) {
                    $currentBattery->due_type = 'duesoon';
                }
            }
        }

        return $this->renderPage($response, 'batteriesoverview', [
            'batteries' => $batteries,
            'current' => $currentBatteries,
            'nextXDays' => $nextXDays,
            'userfields' => $this->getUserfieldsService()->getFields('batteries'),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('batteries')
        ]);
    }

    public function trackChargeCycle(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'batterytracking', [
            'batteries' => $this->getDatabase()->batteries()->where('active = 1')->orderBy('name', 'COLLATE NOCASE'),
            'userfields' => $this->getUserfieldsService()->getFields('battery_charge_cycles')
        ]);
    }

    public function batteryGrocycodeImage(Request $request, Response $response, array $args)
    {
        $gc = new Grocycode(Grocycode::BATTERY, $args['batteryId']);
        return $this->serveGrocycodeImage($request, $response, $gc);
    }
}
