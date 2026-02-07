<?php
/*
South African Theological Seminary
 */

namespace local_satsmail\message\inbound;

use local_satsmail\course;
use local_satsmail\exception;
use local_satsmail\message;
use local_satsmail\message_data;
use local_satsmail\user;
use local_satsmail\event;

/**
 * Inbound message handler for SATS Mail reply-by-email.
 *
 * Processes email replies to SATS Mail notification emails and creates
 * new satsmail messages from the reply content.
 */
class reply_handler extends \core\message\inbound\handler {

    /**
     * Returns the name of the handler for the admin UI.
     *
     * @return string
     */
    protected function get_name(): string {
        return get_string('inboundreply_handler_name', 'local_satsmail');
    }

    /**
     * Returns the description of the handler for the admin UI.
     *
     * @return string
     */
    protected function get_description(): string {
        return get_string('inboundreply_handler', 'local_satsmail');
    }

    /**
     * Process an incoming email reply and create a satsmail message.
     *
     * @param \stdClass $record The parsed inbound data record.
     *     - $record->datavalue: the satsmail message ID being replied to
     *     - $record->user: the Moodle user object of the sender
     * @param \stdClass $messagedata The email content.
     *     - $messagedata->plain: plain text body
     *     - $messagedata->html: HTML body
     *     - $messagedata->subject: email subject
     *     - $messagedata->attachments: array with 'attachment' and 'inline' sub-arrays
     * @return message|false The created message on success, false on failure.
     */
    public function process_message(\stdClass $record, \stdClass $messagedata) {
        global $DB, $USER;

        $originalmessageid = (int) $record->datavalue;
        $replyinguserid = (int) $record->user->id;

        debugging(
            "local_satsmail inbound: Processing reply to message {$originalmessageid} from user {$replyinguserid}",
            DEBUG_DEVELOPER
        );

        // Load the replying user.
        $replyinguser = user::get($replyinguserid);
        if ($replyinguser->deleted) {
            debugging(
                "local_satsmail inbound: User {$replyinguserid} is deleted, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_erroruserdeleted',
                'local_satsmail'
            );
        }

        // Set $USER for file operations and message creation.
        $previoususer = $USER;
        \core\session\manager::set_user($record->user);

        try {
            return $this->process_reply($originalmessageid, $replyinguser, $messagedata);
        } finally {
            // Restore previous user context.
            \core\session\manager::set_user($previoususer);
        }
    }

    /**
     * Process the reply after user context is set.
     *
     * @param int $originalmessageid The ID of the original satsmail message.
     * @param user $replyinguser The user sending the reply.
     * @param \stdClass $messagedata The email content.
     * @return message The created message.
     */
    private function process_reply(int $originalmessageid, user $replyinguser, \stdClass $messagedata): message {
        global $DB;

        // Load the original message.
        try {
            $originalmessage = message::get($originalmessageid);
        } catch (exception $e) {
            debugging(
                "local_satsmail inbound: Original message {$originalmessageid} not found",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errormessagenotfound',
                'local_satsmail'
            );
        }

        debugging(
            "local_satsmail inbound: Found original message {$originalmessageid} "
            . "in course {$originalmessage->course->id}, "
            . "subject: \"{$originalmessage->subject}\"",
            DEBUG_DEVELOPER
        );

        // Validate the original message is not a draft.
        if ($originalmessage->draft) {
            debugging(
                "local_satsmail inbound: Original message {$originalmessageid} is a draft, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errormessageisdraft',
                'local_satsmail'
            );
        }

        // Validate the replying user was a participant in the original message.
        $isparticipant = $originalmessage->sender()->id === $replyinguser->id
            || $originalmessage->has_recipient($replyinguser);
        if (!$isparticipant) {
            debugging(
                "local_satsmail inbound: User {$replyinguser->id} is not a participant "
                . "in message {$originalmessageid}, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errornotparticipant',
                'local_satsmail'
            );
        }

        // Validate enrollment and capability.
        if (!$replyinguser->can_use_mail($originalmessage->course)) {
            debugging(
                "local_satsmail inbound: User {$replyinguser->id} cannot use mail "
                . "in course {$originalmessage->course->id}, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errorcannotuse',
                'local_satsmail'
            );
        }

        // Check the message hasn't been deleted by this user.
        $deletedstatus = $originalmessage->deleted($replyinguser);
        if ($deletedstatus >= message::DELETED_FOREVER) {
            debugging(
                "local_satsmail inbound: Message {$originalmessageid} is permanently deleted "
                . "for user {$replyinguser->id}, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errormessagedeleted',
                'local_satsmail'
            );
        }

        // Process email content -- strip quoted reply text.
        list($content, $format) = self::remove_quoted_text($messagedata);

        if (empty(trim(strip_tags($content)))) {
            debugging(
                "local_satsmail inbound: Reply content is empty after stripping quotes, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_erroremptycontent',
                'local_satsmail'
            );
        }

        debugging(
            "local_satsmail inbound: Processed reply content, format={$format}, "
            . "length=" . strlen($content),
            DEBUG_DEVELOPER
        );

        // Create the reply message using the existing reply mechanism.
        // This creates a draft with the correct recipients and references.
        $data = message_data::reply($originalmessage, $replyinguser, false);

        // Verify there are recipients.
        if (empty($data->to) && empty($data->cc) && empty($data->bcc)) {
            debugging(
                "local_satsmail inbound: No valid recipients for reply, aborting",
                DEBUG_DEVELOPER
            );
            throw new \core\message\inbound\processing_failed_exception(
                'inboundreply_errornorecipients',
                'local_satsmail'
            );
        }

        $replymessage = message::create($data);

        debugging(
            "local_satsmail inbound: Created reply draft with ID {$replymessage->id}",
            DEBUG_DEVELOPER
        );

        // Update the draft with the email content and attachments.
        $context = $originalmessage->course->get_context();
        $draftitemid = file_get_unused_draft_itemid();

        // Store email attachments in the draft area.
        $attachmentcount = $this->process_attachments($messagedata, $draftitemid, $replyinguser);

        debugging(
            "local_satsmail inbound: Processed {$attachmentcount} attachments, draftitemid={$draftitemid}",
            DEBUG_DEVELOPER
        );

        // Update the message data with the email content.
        $updatedata = message_data::new($originalmessage->course, $replyinguser);
        $updatedata->subject = $data->subject;
        $updatedata->content = $content;
        $updatedata->format = $format;
        $updatedata->draftitemid = $draftitemid;
        $updatedata->to = $data->to;
        $updatedata->cc = $data->cc;
        $updatedata->bcc = $data->bcc;

        $replymessage->update($updatedata);

        debugging(
            "local_satsmail inbound: Updated reply message {$replymessage->id} with content",
            DEBUG_DEVELOPER
        );

        // Send the message.
        $replymessage->send(time());

        event\message_sent::create_from_message($replymessage)->trigger();

        debugging(
            "local_satsmail inbound: Sent reply message {$replymessage->id} successfully",
            DEBUG_DEVELOPER
        );

        // Send notifications to recipients (same as web-based send).
        global $PAGE;
        $renderer = $PAGE->get_renderer('local_satsmail');
        foreach ($replymessage->recipients() as $recipient) {
            $notificationid = message_send($renderer->notification($replymessage, $recipient));
            if ($notificationid && get_user_preferences('local_satsmail_markasread', false, $recipient->id)) {
                $replymessage->set_unread($recipient, false);
            }
        }

        debugging(
            "local_satsmail inbound: Notifications sent for reply message {$replymessage->id}",
            DEBUG_DEVELOPER
        );

        return $replymessage;
    }

    /**
     * Process email attachments and store them in a draft file area.
     *
     * @param \stdClass $messagedata The email message data.
     * @param int $draftitemid The draft item ID to store files in.
     * @param user $user The user context for file storage.
     * @return int Number of attachments processed.
     */
    private function process_attachments(\stdClass $messagedata, int $draftitemid, user $user): int {
        global $USER;

        $count = 0;
        $attachments = $messagedata->attachments['attachment'] ?? [];
        $options = message_data::file_options();
        $usercontext = \context_user::instance($user->id);

        $fs = get_file_storage();

        foreach ($attachments as $attachment) {
            if ($options['maxfiles'] > 0 && $count >= $options['maxfiles']) {
                debugging(
                    "local_satsmail inbound: Maximum attachment count ({$options['maxfiles']}) reached, "
                    . "skipping remaining attachments",
                    DEBUG_DEVELOPER
                );
                break;
            }

            if ($options['maxbytes'] > 0 && $attachment->filesize > $options['maxbytes']) {
                debugging(
                    "local_satsmail inbound: Attachment \"{$attachment->filename}\" "
                    . "({$attachment->filesize} bytes) exceeds max size ({$options['maxbytes']}), skipping",
                    DEBUG_DEVELOPER
                );
                continue;
            }

            $filerecord = [
                'contextid' => $usercontext->id,
                'component' => 'user',
                'filearea' => 'draft',
                'itemid' => $draftitemid,
                'filepath' => '/',
                'filename' => $attachment->filename,
            ];

            // Ensure unique filename.
            $existingfile = $fs->get_file(
                $filerecord['contextid'],
                $filerecord['component'],
                $filerecord['filearea'],
                $filerecord['itemid'],
                $filerecord['filepath'],
                $filerecord['filename']
            );
            if ($existingfile) {
                $pathinfo = pathinfo($attachment->filename);
                $filerecord['filename'] = $pathinfo['filename'] . '_' . time()
                    . '.' . ($pathinfo['extension'] ?? 'dat');
            }

            $fs->create_file_from_pathname($filerecord, $attachment->filepath);
            $count++;

            debugging(
                "local_satsmail inbound: Stored attachment \"{$filerecord['filename']}\" "
                . "({$attachment->filesize} bytes)",
                DEBUG_DEVELOPER
            );
        }

        return $count;
    }

    /**
     * Returns a success notification message to send back to the user.
     *
     * @param \stdClass $messagedata The email message data.
     * @param mixed $handlerresult The result from process_message().
     * @return \stdClass|false The notification message, or false for no notification.
     */
    public function get_success_message(\stdClass $messagedata, $handlerresult) {
        if (!($handlerresult instanceof message)) {
            return false;
        }

        $a = new \stdClass();
        $a->subject = $handlerresult->subject;

        $message = new \stdClass();
        $message->plain = get_string('inboundreply_success', 'local_satsmail', $a);
        $message->html = get_string('inboundreply_success', 'local_satsmail', $a);

        return $message;
    }
}
