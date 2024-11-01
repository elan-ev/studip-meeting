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


class TemplateUpload extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $queryParams = $request->getParsedBody();
            if (!isset($queryParams['page'])) {
                throw new Error('Missing page parameter', 422);
            }
            $page = filter_var(htmlspecialchars($queryParams['page'], FILTER_SANITIZE_NUMBER_INT));
            $message = [
                'type' => 'error',
                'text' => _('Folie/Template kann nicht hochgeladen werden')
            ];
            if ($uploadedFiles
                && $page && (isset($uploadedFiles['php'])
                || isset($uploadedFiles['pdf']))
                && DefaultSlideHelper::getInstance()->uploadTemplate($uploadedFiles, $page)) {
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
