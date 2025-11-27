<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class SystemApiController extends BaseApiController
{
    public function getConfig(Request $request, Response $response, array $args)
    {
        try {
            $constants = get_defined_constants();

            // Some GROCY_* constants are not really config settings and therefore should not be exposed
            unset($constants['GROCY_AUTHENTICATED'], $constants['GROCY_DATAPATH'], $constants['GROCY_IS_EMBEDDED_INSTALL'], $constants['GROCY_USER_ID']);

            $returnArray = [];

            foreach ($constants as $constant => $value) {
                if (str_starts_with($constant, 'GROCY_')) {
                    $returnArray[substr($constant, 6)] = $value;
                }
            }

            return $this->apiResponse($response, $returnArray);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function getDbChangedTime(Request $request, Response $response, array $args)
    {
        return $this->apiResponse($response, [
            'changed_time' => $this->getDatabaseService()->getDbChangedTime()
        ]);
    }

    public function getSystemInfo(Request $request, Response $response, array $args)
    {
        return $this->apiResponse($response, $this->getApplicationService()->getSystemInfo());
    }

    public function getSystemTime(Request $request, Response $response, array $args)
    {
        try {
            $offset = 0;
            $params = $request->getQueryParams();
            if (isset($params['offset'])) {
                if (filter_var($params['offset'], FILTER_VALIDATE_INT) === false) {
                    throw new Exception('Query parameter "offset" is not a valid integer');
                }

                $offset = $params['offset'];
            }

            return $this->apiResponse($response, $this->getApplicationService()->getSystemTime($offset));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function logMissingLocalization(Request $request, Response $response, array $args)
    {
        if (GROCY_MODE === 'dev') {
            try {
                $requestBody = $this->getParsedAndFilteredRequestBody($request);

                $this->getLocalizationService()->checkAndAddMissingTranslationToPot($requestBody['text']);
                return $this->emptyApiResponse($response);
            } catch (\Exception $ex) {
                return $this->genericErrorResponse($response, $ex->getMessage());
            }
        }
    }

    public function getLocalizationStrings(Request $request, Response $response, array $args)
    {
        return $this->apiResponse($response, json_decode((string) $this->getLocalizationService()->getPoAsJsonString()), true);
    }
}
