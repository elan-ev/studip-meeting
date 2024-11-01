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
            $queryParams = $request->getParsedBody();
            if (!isset($queryParams['type'])) {
                throw new Error('Missing type parameter', 422);
            }
            $type = htmlspecialchars($queryParams['type']);
            $message = [
                'type' => 'error',
                'text' => _('Schriftart kann nicht hochgeladen werden')
            ];
            if ($uploadedFiles && isset($uploadedFiles['font']) && DefaultSlideHelper::getInstance()->uploadFont($uploadedFiles['font'], $type)) {
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
