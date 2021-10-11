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


class TemplateRead extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $installed_templates = DefaultSlideHelper::getInstalledTemplates();
            // Removing dirnames from the content (security measure)
            foreach ($installed_templates as $page => $template) {
                if (isset($template['pdf']['dirname'])) {
                    unset($installed_templates[$page]['pdf']['dirname']);
                }
                if (isset($template['php']['dirname'])) {
                    unset($installed_templates[$page]['php']['dirname']);
                }
            }
            return $this->createResponse([
                'templates' => $installed_templates,
            ], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
