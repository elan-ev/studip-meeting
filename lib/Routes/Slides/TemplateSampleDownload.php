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


class TemplateSampleDownload extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $what = filter_var($args['what'], FILTER_SANITIZE_STRING);
            if ($sample_file_content = DefaultSlideHandler::downloadSampleTemplate($what)) {
                return $this->createResponse([
                    'content' => $sample_file_content,
                ], $response);
            } else {
                $message = [
                    'type' => 'error',
                    'text' => _('Mustervorlage konnte nicht gefunden werden')
                ];
                return $this->createResponse([
                    'message' => $message,
                ], $response);
            }
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
