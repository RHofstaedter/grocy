<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class GenericEntityController extends BaseController
{
    public function userentitiesList(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'userentities', [
            'userentities' => $this->getDatabase()->userentities()->orderBy('name', 'COLLATE NOCASE')
        ]);
    }

    public function userentityEditForm(Request $request, Response $response, array $args)
    {
        if ($args['userentityId'] == 'new') {
            return $this->renderPage($response, 'userentityform', [
                'mode' => 'create'
            ]);
        } else {
            return $this->renderPage($response, 'userentityform', [
                'mode' => 'edit',
                'userentity' => $this->getDatabase()->userentities($args['userentityId'])
            ]);
        }
    }

    public function userfieldEditForm(Request $request, Response $response, array $args)
    {
        if ($args['userfieldId'] == 'new') {
            return $this->renderPage($response, 'userfieldform', [
                'mode' => 'create',
                'userfieldTypes' => $this->getUserfieldsService()->getFieldTypes(),
                'entities' => $this->getUserfieldsService()->getEntities()
            ]);
        } else {
            return $this->renderPage($response, 'userfieldform', [
                'mode' => 'edit',
                'userfield' => $this->getUserfieldsService()->getField($args['userfieldId']),
                'userfieldTypes' => $this->getUserfieldsService()->getFieldTypes(),
                'entities' => $this->getUserfieldsService()->getEntities()
            ]);
        }
    }

    public function userfieldsList(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'userfields', [
            'userfields' => $this->getUserfieldsService()->getAllFields(),
            'entities' => $this->getUserfieldsService()->getEntities()
        ]);
    }

    public function userobjectEditForm(Request $request, Response $response, array $args)
    {
        $userentity = $this->getDatabase()->userentities()->where('name = :1', $args['userentityName'])->fetch();

        if ($args['userobjectId'] == 'new') {
            return $this->renderPage($response, 'userobjectform', [
                'userentity' => $userentity,
                'mode' => 'create',
                'userfields' => $this->getUserfieldsService()->getFields('userentity-' . $args['userentityName'])
            ]);
        } else {
            return $this->renderPage($response, 'userobjectform', [
                'userentity' => $userentity,
                'mode' => 'edit',
                'userobject' => $this->getDatabase()->userobjects($args['userobjectId']),
                'userfields' => $this->getUserfieldsService()->getFields('userentity-' . $args['userentityName'])
            ]);
        }
    }

    public function userobjectsList(Request $request, Response $response, array $args)
    {
        $userentity = $this->getDatabase()->userentities()->where('name = :1', $args['userentityName'])->fetch();

        return $this->renderPage($response, 'userobjects', [
            'userentity' => $userentity,
            'userobjects' => $this->getDatabase()->userobjects()->where('userentity_id = :1', $userentity->id),
            'userfields' => $this->getUserfieldsService()->getFields('userentity-' . $args['userentityName']),
            'userfieldValues' => $this->getUserfieldsService()->getAllValues('userentity-' . $args['userentityName'])
        ]);
    }
}
