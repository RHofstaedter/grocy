<?php

namespace Grocy\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CalendarController extends BaseController
{
    public function overview(Request $request, Response $response, array $args)
    {
        return $this->renderPage($response, 'calendar', [
            'fullcalendarEventSources' => $this->getCalendarService()->getEvents()
        ]);
    }
}
