<?php

namespace Meetings\Routes\Folder;

/**
 *
 * @author Farbod Zamani <zamani@elan-ev.de>
 */

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\Errors\AuthorizationFailedException;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Exception;
use Meetings\Models\I18N;

use ElanEv\Model\MeetingCourse;
use ElanEv\Model\Meeting;

class FolderList extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Returns the info about folders
     *
     * @param string $folder_id folder id
     * @param string $cid course id
     *
     *
     * @return json room parameter
     *
     * @throws \Error in case of failure or wrong folder data
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $cid = htmlspecialchars($args['cid']);
        $folder_id = htmlspecialchars($args['folder_id']);

        try {

            $folder_obj = [];

            if (strtolower($folder_id) == 'topfolder') {
                $folder = \Folder::findTopFolder($cid);
            } else {
                $folder = \Folder::find($folder_id);
            }

            if (!$folder) {
                return $this->createResponse([
                    'message'=> [
                        "type" => "error",
                        "text" => I18N::_('Fehler beim Laden des Hauptordners!')
                    ],
                ], $response);
                die;
            }

            $standard_folder = $folder->getTypedFolder();

            $folder_obj['name'] =  $folder->name;
            $folder_obj['id']   =  $standard_folder->getId();
            $folder_obj['redirect_link'] = \URLHelper::getUrl('dispatch.php/course/files/index/' . $standard_folder->getId() . '?cid=' . $cid);

            $subfolders = [];
            foreach ($standard_folder->subfolders as $subfolder) {
                $icon = $subfolder->getTypedFolder()->getIcon('clickable')->getShape();
                $subfolders[$subfolder->id] = ["name" => $subfolder->name, "icon" => $icon];
            }

            $folder_obj['subfolders'] = $subfolders;

            $files = [];
            foreach ($standard_folder->getFiles() as $file_ref) {
                $files[] = [
                    'id' => $file_ref->id,
                    'name' => $file_ref->name,
                    'icon' => \FileManager::getIconForFileRef($file_ref)->getShape()
                ];
            }

            $folder_obj['files'] = $files;

            $breadcrumbs = [];
            $brfolder = $standard_folder;
            do {
                $breadcrumbs[$brfolder->id] = $brfolder->name;
            } while ($brfolder = $brfolder->getParent());
            $breadcrumbs = array_reverse($breadcrumbs);
            $root_dir    = array_shift($breadcrumbs);
            $last_crumb  = end($breadcrumbs);

            $folder_obj['breadcrumbs'] = $breadcrumbs;


            $folder_obj['folder_types'] = [];
            if (!is_a($standard_folder, 'VirtualFolderType')) {
                $folder_types = \FileManager::getAvailableFolderTypes($standard_folder->range_id, $GLOBALS['user']->id);

                $allowed_folder_types = ['StandardFolder', 'HiddenFolder'];
                foreach ($folder_types as $folder_type) {
                    if (!in_array($folder_type, $allowed_folder_types)) {
                        continue;
                    }
                    $folder_type_instance = new $folder_type(
                        ['range_id' => $standard_folder->range_id,
                        'range_type' => $standard_folder->range_type,
                        'parent_id' => $standard_folder->getId()]
                    );
                    $folder_obj['folder_types'][] = [
                        'class'    => $folder_type,
                        'name'     => $folder_type::getTypeName(),
                        'icon'     => $folder_type_instance->getIcon('clickable')->getShape()
                    ];
                }
            }

            return $this->createResponse([
                'folder'=> $folder_obj,
            ], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }

}
