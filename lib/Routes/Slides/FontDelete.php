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


class FontDelete extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $font_type = filter_var($args['font_type'], FILTER_SANITIZE_STRING);
            $message = [
                'type' => 'error',
                'text' => _('Schriftart kann nicht gelöscht werden')
            ];
            if ($font_type && DefaultSlideHandler::deleteFont($font_type)) {
                $message = [
                    'type' => 'success',
                    'text' => _('Schriftart wurde erfolgreich gelöscht')
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
