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


class TemplateUpload extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $page = filter_var($request->getParam('page'), FILTER_SANITIZE_NUMBER_INT);
            $message = [
                'type' => 'error',
                'text' => _('Folie/Template kann nicht hochgeladen werden')
            ];
            if ($uploadedFiles && $page && (isset($uploadedFiles['php']) || isset($uploadedFiles['pdf'])) && DefaultSlideHandler::uploadTemplate($uploadedFiles, $page)) {
                $message = [
                    'type' => 'success',
                    'text' => _('Folie/Template wurde erfolgreich hochgeladen')
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
