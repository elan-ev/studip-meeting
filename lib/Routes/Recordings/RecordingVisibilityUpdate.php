<?php

namespace Meetings\Routes\Recordings;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Meetings\MeetingsTrait;
use Meetings\MeetingsController;
use Meetings\Errors\Error;
use Meetings\Models\I18N;
use ElanEv\Model\MeetingCourse;
use ElanEv\Driver\DriverFactory;
use ElanEv\Model\Driver;
use Exception;

class RecordingVisibilityUpdate extends MeetingsController
{
    use MeetingsTrait;

    public function __invoke(Request $request, Response $response, $args)
    {
        global $perm;

        $cid = $args['cid'];
        if (!$perm->have_studip_perm('tutor', $cid)) {
            throw new Error('Access Denied', 403);
        }

        $data = $this->getRequestData($request);
        $visibility = $data['visibility'] ?? '';
        if (!in_array($visibility, ['teachers', 'participants', 'public'], true)) {
            throw new Error('Invalid recording visibility', 400);
        }

        $recordingIds = isset($args['recordings_id'])
            ? [$args['recordings_id']]
            : ($data['recording_ids'] ?? []);
        $recordingIds = array_values(array_filter($recordingIds, function ($recordingId) {
            return is_string($recordingId) && $recordingId !== '';
        }));
        if (!$recordingIds) {
            throw new Error('No recordings selected', 400);
        }

        try {
            $meetingCourse = new MeetingCourse([$args['room_id'], $cid]);
            if ($meetingCourse->isNew()) {
                throw new Error('Room not found', 404);
            }

            $factory = new DriverFactory(Driver::getConfig());
            $driver = $factory->getDriver(
                $meetingCourse->meeting->driver,
                $meetingCourse->meeting->server_index
            );

            $roomRecordings = $driver->getRecordings($meetingCourse->meeting->getMeetingParameters());
            $roomRecordingIds = [];
            if (!empty($roomRecordings)) {
                foreach ($roomRecordings as $recording) {
                    $roomRecordingIds[] = (string) $recording->recordID;
                }
            }
            if (array_diff($recordingIds, $roomRecordingIds)) {
                throw new Error('Recording not found', 404);
            }

            if (!method_exists($driver, 'setRecordingVisibility')
                || !$driver->setRecordingVisibility($recordingIds, $visibility)) {
                return $this->createResponse(['message' => [
                    'text' => I18N::_('Sichtbarkeit der Aufzeichnung konnte nicht geändert werden.'),
                    'type' => 'error',
                ]], $response);
            }

            return $this->createResponse(['message' => [
                'text' => I18N::_('Sichtbarkeit der Aufzeichnung wurde geändert.'),
                'type' => 'success',
            ]], $response);
        } catch (Exception $e) {
            throw new Error($e->getMessage(), $e->getCode() ?: 404);
        }
    }
}
