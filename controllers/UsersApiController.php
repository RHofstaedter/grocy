<?php

namespace Grocy\Controllers;

use Grocy\Controllers\Users\User;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Exception;

class UsersApiController extends BaseApiController
{
    public function addPermission(Request $request, Response $response, array $args)
    {
        try {
            User::checkPermission($request, User::PERMISSION_ADMIN);
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $this->getDatabase()->user_permissions()->createRow([
                'user_id' => $args['userId'],
                'permission_id' => $requestBody['permission_id']
            ])->save();
            return $this->emptyApiResponse($response);
        } catch (\Slim\Exception\HttpSpecializedException $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage(), $ex->getCode());
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function createUser(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_USERS_CREATE);
        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            if ($requestBody === null) {
                throw new Exception('Request body could not be parsed (probably invalid JSON format or missing/wrong Content-Type header)');
            }

            $this->getUsersService()->createUser($requestBody['username'], $requestBody['first_name'], $requestBody['last_name'], $requestBody['password'], $requestBody['picture_file_name']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function deleteUser(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_USERS_EDIT);
        try {
            $this->getUsersService()->deleteUser($args['userId']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function editUser(Request $request, Response $response, array $args)
    {
        if ($args['userId'] == GROCY_USER_ID) {
            User::checkPermission($request, User::PERMISSION_USERS_EDIT_SELF);
        } else {
            User::checkPermission($request, User::PERMISSION_USERS_EDIT);
        }

        $requestBody = $this->getParsedAndFilteredRequestBody($request);

        try {
            $this->getUsersService()->editUser($args['userId'], $requestBody['username'], $requestBody['first_name'], $requestBody['last_name'], $requestBody['password'], $requestBody['picture_file_name']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function getUserSetting(Request $request, Response $response, array $args)
    {
        try {
            $value = $this->getUsersService()->getUserSetting(GROCY_USER_ID, $args['settingKey']);
            return $this->apiResponse($response, ['value' => $value]);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function getUserSettings(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getUsersService()->getUserSettings(GROCY_USER_ID));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function getUsers(Request $request, Response $response, array $args)
    {
        User::checkPermission($request, User::PERMISSION_USERS_READ);
        try {
            return $this->filteredApiResponse($response, $this->getUsersService()->getUsersAsDto(), $request->getQueryParams());
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function currentUser(Request $request, Response $response, array $args)
    {
        try {
            return $this->apiResponse($response, $this->getUsersService()->getUsersAsDto()->where('id', GROCY_USER_ID));
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function listPermissions(Request $request, Response $response, array $args)
    {
        try {
            User::checkPermission($request, User::PERMISSION_ADMIN);

            return $this->apiResponse(
                $response,
                $this->getDatabase()->user_permissions()->where('user_id', $args['userId'])
            );
        } catch (\Slim\Exception\HttpSpecializedException $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage(), $ex->getCode());
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function setPermissions(Request $request, Response $response, array $args)
    {
        try {
            User::checkPermission($request, User::PERMISSION_ADMIN);

            $requestBody = $request->getParsedBody();
            $db = $this->getDatabase();
            $db->user_permissions()
                ->where('user_id', $args['userId'])
                ->delete();

            $perms = [];
            if (GROCY_MODE === 'demo' || GROCY_MODE === 'prerelease') {
                // For demo mode always all users have and keep the ADMIN permission
                $perms[] = [
                    'user_id' => $args['userId'],
                    'permission_id' => 1
                ];
            } else {
                foreach ($requestBody['permissions'] as $perm_id) {
                    $perms[] = [
                        'user_id' => $args['userId'],
                        'permission_id' => $perm_id
                    ];
                }
            }

            $db->insert('user_permissions', $perms, 'batch');

            return $this->emptyApiResponse($response);
        } catch (\Slim\Exception\HttpSpecializedException $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage(), $ex->getCode());
        } catch (\Exception $ex) {
            return $this->genericErrorResponse($response, $ex->getMessage());
        }
    }

    public function setUserSetting(Request $request, Response $response, array $args)
    {
        try {
            $requestBody = $this->getParsedAndFilteredRequestBody($request);

            $value = $this->getUsersService()->setUserSetting(GROCY_USER_ID, $args['settingKey'], $requestBody['value']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }

    public function deleteUserSetting(Request $request, Response $response, array $args)
    {
        try {
            $value = $this->getUsersService()->deleteUserSetting(GROCY_USER_ID, $args['settingKey']);
            return $this->emptyApiResponse($response);
        } catch (\Exception $exception) {
            return $this->genericErrorResponse($response, $exception->getMessage());
        }
    }
}
