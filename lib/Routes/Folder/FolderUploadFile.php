<?php

namespace Meetings\Routes\Folder;
/**
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Nyholm\Psr7\UploadedFile;
use Meetings\Models\I18N;


class FolderUploadFile extends MeetingsController
{
    use MeetingsTrait;
    public function __invoke(Request $request, Response $response, $args)
    {
        try {
            $uploadedFiles = $request->getUploadedFiles();
            $queryParams = $request->getParsedBody();
            if (!isset($queryParams['parent_id'])) {
                throw new Error('Missing parent_id parameter', 422);
            }
            $parent_id = htmlspecialchars($queryParams['parent_id']);
            $message = [
                'type' => 'error',
                'text' => I18N::_('Datei kann nicht hochgeladen werden')
            ];
            if ($uploadedFiles && isset($uploadedFiles['upload_file'])) {
                $uploadFile = $uploadedFiles['upload_file'];
                $uploadFileClientFilename = $uploadFile->getClientFilename();
                $temp_file = $GLOBALS['TMP_PATH'] . DIRECTORY_SEPARATOR . md5(uniqid($uploadFileClientFilename, true));
                $uploadFile->moveTo($temp_file);
                $consumableUploadFile = [
                    'tmp_name' => [$temp_file],
                    'name' => [$uploadFileClientFilename],
                    'size' => [$uploadFile->getSize()],
                    'type' => [$uploadFile->getClientMediaType()],
                    'error' => [$uploadFile->getError()]
                ];
                $folder = \Folder::find($parent_id);
                $typedFolder = $folder->getTypedFolder();
                $uploaded = \FileManager::handleFileUpload(
                    $consumableUploadFile,
                    $typedFolder,
                    $GLOBALS['user']->id
                );
                $message = [
                    'type' => 'success',
                    'text' => I18N::_('Datei wurde erfolgreich hochgeladen')
                ];
                if (empty($uploaded['error'])) {
                    $message = [
                        'type' => 'success',
                        'text' => I18N::_('Datei wurde erfolgreich hochgeladen')
                    ];
                }
            }
            return $this->createResponse([
                'message' => $message,
            ], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
