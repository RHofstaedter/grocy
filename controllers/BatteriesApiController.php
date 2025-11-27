<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Grocy\Helpers\WebhookRunner;
use Grocy\Helpers\Grocycode;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class BatteriesApiController extends BaseApiController
{
    public function batteryDetails(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getBatteriesService()->getBatteryDetails($args['batteryId']));
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function current(Request $request, Response $response, array $args)
    {
        return $this->filteredApiResponse(
            $response,
            $this->getBatteriesService()->getCurrent(),
            $request->getQueryParams()
        );
    }

    public function trackChargeCycle(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_BATTERIES_TRACK_CHARGE_CYCLE);

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            $trackedTime = date('Y-m-d H:i:s');
            if (array_key_exists('tracked_time', $requestBody) && IsIsoDateTime($requestBody['tracked_time'])) {
                $trackedTime = $requestBody['tracked_time'];
            }

            $chargeCycleId = $this->getBatteriesService()->trackChargeCycle($args['batteryId'], $trackedTime);
            return $this->apiResponse($response, $this->getDatabase()->battery_charge_cycles($chargeCycleId));
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function undoChargeCycle(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_BATTERIES_UNDO_CHARGE_CYCLE);

        try {
            $this->apiResponse($response, $this->getBatteriesService()->undoChargeCycle($args['chargeCycleId']));
            return $this->emptyApiResponse($response);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function batteryPrintLabel(Request $request, Response $response, array $args)
    {
        try {
            $batteryDetails = (object)$this->getBatteriesService()->getBatteryDetails($args['batteryId']);

            $webhookData = array_merge([
                'battery' => $batteryDetails->battery->name,
                'grocycode' => (string)(new Grocycode(Grocycode::BATTERY, $args['batteryId'])),
                'details' => $batteryDetails,
            ], GROCY_LABEL_PRINTER_PARAMS);

            if (GROCY_LABEL_PRINTER_RUN_SERVER) {
                (new WebhookRunner())->run(GROCY_LABEL_PRINTER_WEBHOOK, $webhookData, GROCY_LABEL_PRINTER_HOOK_JSON);
            }

            return $this->apiResponse($response, $webhookData);
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }
}
