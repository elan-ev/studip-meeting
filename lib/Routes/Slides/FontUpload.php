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
use Meetings\Helpers\DefaultSlideHelper;


class FontUpload extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $type = filter_var($request->getParam('type'), FILTER_SANITIZE_STRING);
            $message = [
                'type' => 'error',
                'text' => _('Schriftart kann nicht hochgeladen werden')
            ];
            if ($uploadedFiles && isset($uploadedFiles['font']) && DefaultSlideHelper::uploadFont($uploadedFiles['font'], $type)) {
                $message = [
                    'type' => 'success',
                    'text' => _('Schriftart wurde erfolgreich hochgeladen')
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
