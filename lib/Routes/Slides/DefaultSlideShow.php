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

use Exception;
use ElanEv\Model\Meeting;
use ElanEv\Model\MeetingToken;

class DefaultSlideShow extends MeetingsController
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
        $meeting_id = filter_var(escapeshellcmd(basename($args['meeting_id'])), FILTER_SANITIZE_STRING);
        $token = filter_var(escapeshellcmd(basename($args['token'])), FILTER_SANITIZE_STRING);
        if (!$meeting_id && !$token) {
            return;
        }
        try {
            $meeting = new Meeting($meeting_id);

            //Token check
            if (!$meeting->meeting_token || !$meeting->meeting_token->is_valid($token)) {
                return;
            }

            $courseid = $meeting->courses[0]->seminar_id;
            if ($meeting->isNew() || !$courseid) {
                return;
            }

            // If there is any template uploaded by admin, we use them only!
            if (DefaultSlideHelper::checkCustomizedTemplates()) {
                $pdf = DefaultSlideHelper::generateCustomizedPDF($meeting);
            } else { // Otherwise, we go for the studip default pdf generator system.
                $pdf = DefaultSlideHelper::generateStudIPDefaultPDF($meeting);
            }

            if (!$pdf) {
                return;
            }

            // Output the pdf file.
            $temp_file = $GLOBALS['TMP_PATH'] . '/' . md5(uniqid('pdf-file', true));
            $pdf->Output($temp_file, 'F');
            $content_type = 'application/pdf';
            $filesize = @filesize($temp_file);

            if (!$filesize) {
                return;
            }

            // close session, download will mostly be a parallel action
            page_close();

            // output_buffering may be explicitly or implicitly enabled
            while (ob_get_level()) {
                ob_end_clean();
            }

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
            header("Content-Disposition: $content_disposition; " . encode_header_parameter('filename', 'default.pdf'));
            readfile_chunked($temp_file, $start, $end);

            unlink($temp_file);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), 404);
        }
    }
}
