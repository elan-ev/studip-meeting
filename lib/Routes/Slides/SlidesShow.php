<?php

namespace Meetings\Routes\Slides;

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
use ElanEv\Model\MeetingToken;

class SlidesShow extends MeetingsController
{
    use MeetingsTrait;
    /**
     * Prepares and shows files
     *
     * @param string $meeting_id meeting id
     * @param string $slide_id slide id
     * @param string $token token id
     *
     *
     * @return File the specific file on header
     *
     * @throws \Error in case of failure or wrong folder data
     */
    public function __invoke(Request $request, Response $response, $args)
    {
        $meeting_id = htmlspecialchars(escapeshellcmd(basename($args['meeting_id'])));
        $silde_id = htmlspecialchars(escapeshellcmd(basename($args['slide_id'])));
        $token = htmlspecialchars(escapeshellcmd(basename($args['token'])));
        if (!$meeting_id && !$silde_id && !$token) {
            return;
        }
        try {
            $file_ref = \FileRef::find($silde_id);
            $meeting = new Meeting($meeting_id);

            //Token check
            if (!$meeting->meeting_token || !$meeting->meeting_token->is_valid($token)) {
                return;
            }

            $folder = $file_ref->folder->getTypedFolder();
            if ($meeting->isNew() || empty($meeting->folder_id) || !$file_ref || $folder->getId() != $meeting->folder_id) {
                return;
            }

            $path_file = '';
            $filesize = 0;
            $is_url_file = false;
            $redirect_url_file = '';
            if (method_exists($file_ref, 'getFileType')) { // StudIP >= 4.6
                $path_file = is_a($file_ref->file->filetype, "URLFile")
                       ? $file_ref->file->metadata['url']
                       : $file_ref->file->path;
                $is_url_file =  !is_a($file_ref->file->filetype, "URLFile") ? false : true;
                $redirect_url_file = $file_ref->file->metadata['access_type'];
                $filesize = $file_ref->getFileType()->getSize();
            } else { // StudIP < 4.6
                $path_file = $file_ref->file->storage == 'disk'
                        ? $file_ref->file->path
                        : $file_ref->file->url;
                $is_url_file = $file_ref->file->storage == 'disk' ? false : true;
                $redirect_url_file = $file_ref->file->url_access_type;
                $filesize = @filesize($path_file);
            }

            if (!$filesize) {
                return;
            }
            if ($is_url_file && $redirect_url_file == 'redirect') {
                header('Location: ' . $path_file);
                die();
            }
            $content_type = $file_ref->mime_type ?: get_mime_type($file_name);
            // close session, download will mostly be a parallel action
            page_close();

            // output_buffering may be explicitly or implicitly enabled
            while (ob_get_level()) {
                ob_end_clean();
            }

            if ($filesize && !$is_url_file) {
                header("Accept-Ranges: bytes");
                $start = 0;
                $end = $filesize - 1;
                $length = $filesize;
                if (isset($_SERVER['HTTP_RANGE'])) {
                    $c_start = $start;
                    $c_end   = $end;
                    list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
                    if (mb_strpos($range, ',') !== false) {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        header("Content-Range: bytes $start-$end/$filesize");
                        exit;
                    }
                    if ($range[0] == '-') {
                        $c_start = $filesize - mb_substr($range, 1);
                    } else {
                        $range  = explode('-', $range);
                        $c_start = $range[0];
                        $c_end   = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $filesize;
                    }
                    $c_end = ($c_end > $end) ? $end : $c_end;
                    if ($c_start > $c_end || $c_start > $filesize - 1 || $c_end >= $filesize) {
                        header('HTTP/1.1 416 Requested Range Not Satisfiable');
                        header("Content-Range: bytes $start-$end/$filesize");
                        exit;
                    }
                    $start  = $c_start;
                    $end    = $c_end;
                    $length = $end - $start + 1;
                    header('HTTP/1.1 206 Partial Content');
                }
                header("Content-Range: bytes $start-$end/$filesize");
                header("Content-Length: $length");
            } elseif ($filesize) {
                header("Content-Length: $filesize");
            }
            
            header("Expires: Mon, 12 Dec 2001 08:00:00 GMT");
            header("Last-Modified: " . gmdate ("D, d M Y H:i:s") . " GMT");
            if ($_SERVER['HTTPS'] == "on"){
                header("Pragma: public");
                header("Cache-Control: private");
            } else {
                header("Pragma: no-cache");
                header("Cache-Control: no-store, no-cache, must-revalidate");   // HTTP/1.1
            }
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Content-Type: $content_type");
            header("Content-Disposition: $content_disposition; " . encode_header_parameter('filename', $file_name));
            readfile_chunked($path_file, $start, $end);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
