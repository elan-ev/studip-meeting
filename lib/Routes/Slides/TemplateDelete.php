<?php

namespace Meetings\Routes\Slides;

/**
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Meetings\DefaultSlideHandler;


class TemplateDelete extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $page = filter_var($args['page'], FILTER_SANITIZE_NUMBER_INT);
            $what = filter_var($args['what'], FILTER_SANITIZE_STRING);
            $message = [
                'type' => 'error',
                'text' => _('Folie/Template kann nicht gelöscht werden')
            ];
            if (DefaultSlideHandler::deleteTemplate($page, $what)) {
                $message = [
                    'type' => 'success',
                    'text' => _('Folie/Template wurde erfolgreich gelöscht')
                ];
            }
            return $this->createResponse([
                'message' => $message,
            ], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
