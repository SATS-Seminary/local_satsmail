<?php
/*
South African Theological Seminary
 */

namespace local_satsmail\output;

use local_satsmail\message;
use local_satsmail\user;

class renderer extends \plugin_renderer_base {
    /**
     * Returns the URL of the icon representing the format of a file.
     *
     * @param \stored_file $file File object.
     * @return string
     */
    public function file_icon_url(\stored_file $file): string {
        global $CFG;

        // Size is deprecated since Moodle 4.3.
        $size = $CFG->branch >= 403 ? null : 24;

        return $this->image_url(file_file_icon($file, $size))->out(false);
    }

    /**
     * Returns the URL of a file.
     *
     * @param \stored_file $file File object.
     * @return string
     */
    public function file_url(\stored_file $file): string {
        $fileurl = \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename()
        );
        return $fileurl->out(false);
    }

    /**
     * Returns the formatted content of a message.
     *
     * @param message $message Message.
     * @return string
     */
    public function formatted_message_content(message $message): string {
        global $CFG;

        require_once("$CFG->libdir/filelib.php");

        $context = $message->course->get_context();
        $content = file_rewrite_pluginfile_urls(
            $message->content,
            'pluginfile.php',
            $context->id,
            'local_satsmail',
            'message',
            $message->id
        );

        return format_text($content, $message->format, ['context' => $context, 'filter' => true, 'para' => false]);
    }

    /**
     * Returns a formatted date and time.
     *
     * The format used depends on the current date:
     * - Same day: hour and minute.
     * - Same year: abbreviated month and day.
     * - Previous years: year, month number and day.
     *
     * @param int $time Unix timestamp.
     * @param bool $full Return full date and time.
     * @param ?int $now Unix timestamp to use as "now" time.
     * @return string
     */
    public function formatted_time(int $time, $full = false, $now = null): string {
        $now = $now ?? time();
        $tz = new  \DateTimeZone(\core_date::get_user_timezone());

        $today = new \DateTime();
        $today->setTimezone($tz);
        $today->setTimestamp($now);
        $today->setTime(0, 0, 0);

        $year = new \DateTime();
        $year->setTimezone($tz);
        $year->setTimestamp($now);
        $year->setDate($year->format('Y'), 1, 1);
        $year->setTime(0, 0, 0);

        if ($full) {
            return  userdate($time, get_string('strftimedatetime', 'langconfig'));
        } else if ($time > $now) {
            return userdate($time, get_string('strftimedatefullshort', 'langconfig'));
        } else if ($time >= $today->getTimestamp()) {
            return userdate($time, get_string('strftimetime', 'langconfig'));
        } else if ($time >= $year->getTimestamp()) {
            return userdate($time, get_string('strftimedateshortmonthabbr', 'langconfig'));
        } else {
            return userdate($time, get_string('strftimedatefullshort', 'langconfig'));
        }
    }

    /**
     * Returns the notification for a recipient of a message.
     *
     * @param message $message Message.
     * @param user $recipient Recipient.
     * @return \core\message\message
     */
    public function notification(message $message, user $recipient): \core\message\message {
        global $CFG, $SITE;

        assert(!$message->draft);
        assert($message->has_recipient($recipient));

        require_once("$CFG->libdir/filelib.php");

        $course = $message->course;
        $context = $course->get_context();
        $sender = $message->sender();

        $url = new \moodle_url('/local/satsmail/view.php', ['t' => 'inbox', 'm' => $message->id]);
        $sitename = format_string($SITE->shortname, true, ['context' => \context_system::instance()]);
        $coursename = format_string($course->fullname, true, ['context' => $context]);
        $content = $this->formatted_message_content($message);
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'local_satsmail', 'message', $message->id, 'filepath, filename', false);
        $attachments = [];
        foreach ($files as $file) {
            $attachments[] = [
                'path' => ltrim($file->get_filepath() . $file->get_filename(), '/'),
                'size' => display_size($file->get_filesize()),
                'url' => $this->file_url($file),
                'icon' => $this->file_icon_url($file),
            ];
        }

        // Check if reply-by-email is available.
        $canreply = false;
        if (\core\message\inbound\manager::is_enabled()) {
            try {
                $addressmanager = new \core\message\inbound\address_manager();
                $addressmanager->set_handler('\local_satsmail\message\inbound\reply_handler');
                $addressmanager->set_data($message->id);
                $replyto = $addressmanager->generate($recipient->id);
                if ($replyto) {
                    // Convert the base64 subaddress to URL-safe base64 (RFC 4648 section 5)
                    // to avoid '/' and '+' characters that cause issues with SES proxies.
                    $replyto = self::make_address_url_safe($replyto);
                    $canreply = true;
                    debugging(
                        "local_satsmail: Set reply-to for message {$message->id} "
                        . "to user {$recipient->id}: {$replyto}",
                        DEBUG_DEVELOPER
                    );
                }
            } catch (\Exception $e) {
                debugging(
                    "local_satsmail: Failed to generate reply-to address for message {$message->id} "
                    . "to user {$recipient->id}: " . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        $templatecontext = [
            'coursename' => $coursename,
            'sendername' => $sender->fullname(),
            'date' => $this->formatted_time($message->time, true),
            'subject' => $message->subject,
            'content' => format_text_email($content, FORMAT_HTML),
            'hasattachments' => count($attachments) > 0,
            'attachments' => $attachments,
            'viewurl' => $url->out(false),
            'canreply' => $canreply,
        ];

        $notification = new \core\message\message();
        $notification->courseid = $message->course->id;
        $notification->component = 'local_satsmail';
        $notification->name = 'mail';
        $notification->userfrom = $sender->id;
        $notification->userto = $recipient->id;
        $notification->subject = strings::get('notificationsubject', $sitename);
        $notification->fullmessage = $this->render_from_template('local_satsmail/notification_text', $templatecontext);
        $notification->fullmessageformat = FORMAT_PLAIN;
        $notification->fullmessagehtml = $this->render_from_template('local_satsmail/notification_html',
            array_merge($templatecontext, [
                'courseurl' => $course->url(),
                'senderurl' => $sender->profile_url($course),
                'content' => $content,
            ])
        );
        $notification->smallmessage = strings::get('notificationsmallmessage', [
            'user' => $sender->fullname(),
            'course' => $course->fullname,
        ]);
        $notification->notification = 1;
        $notification->contexturl = $url->out(false);
        $notification->contexturlname = $message->subject;

        // Set reply-to address for incoming mail handler.
        if ($canreply && !empty($replyto)) {
            $notification->replyto = $replyto;
            $notification->replytoname = $sender->fullname();
        }

        return $notification;
    }

    /**
     * Converts the base64-encoded subaddress in an inbound mail address to use
     * only common email-safe characters.
     *
     * Standard base64 uses '+', '/' and '=' which can cause issues with email
     * proxies like Amazon SES. This replaces them with '-' and '.' in the
     * subaddress portion (between '+' and '@'), and strips '=' padding.
     *
     * See MDL-71652 for the corresponding Moodle core change needed in
     * address_manager::process() to convert back before decoding.
     *
     * @param string $address The full email address with standard base64 subaddress.
     * @return string The address with email-safe subaddress.
     */
    private static function make_address_url_safe(string $address): string {
        // Split into local part and domain.
        $atpos = strrpos($address, '@');
        if ($atpos === false) {
            return $address;
        }
        $localpart = substr($address, 0, $atpos);
        $domain = substr($address, $atpos);

        // Split local part into mailbox and subaddress at the first '+'.
        $pluspos = strpos($localpart, '+');
        if ($pluspos === false) {
            return $address;
        }
        $mailbox = substr($localpart, 0, $pluspos);
        $subaddress = substr($localpart, $pluspos + 1);

        // Replace '+' -> '-', '/' -> '.' and strip '=' padding.
        // Per MDL-71652: '-' and '.' are common in email addresses and safe for all MTAs.
        $subaddress = str_replace('=', '', strtr($subaddress, '+/', '-.'));

        return $mailbox . '+' . $subaddress . $domain;
    }

    /**
     * Returns the script tags needed for a svelte entry script.
     *
     * CSS files are included in the head.
     *
     * @param $file Source file name, e.g. "src/view.ts"
     * @return string
     */
    public function svelte_script(string $file): string {
        global $CFG;

        $html = '';

        if (!empty($CFG->local_satsmail_devserver)) {
            $jsurl = $CFG->local_satsmail_devserver . '/' . $file;
        } else {
            $manifestpath = $CFG->dirroot . '/local/satsmail/svelte/build/manifest.json';
            $manifest = json_decode(file_get_contents($manifestpath), true);
            if (!$manifest || empty($manifest[$file])) {
                throw new \coding_exception('local_satsmail: invalid svelte manifest or script name "' . $file . '"');
            }
            $jsurl = $CFG->wwwroot . '/local/satsmail/svelte/build/' . $manifest[$file]['file'];
            $chunks = [$file];
            $cssurls = [];
            while ($file = array_pop($chunks)) {
                foreach ($manifest[$file]['imports'] ?? [] as $jsfile) {
                    $chunks[] = $jsfile;
                }
                foreach ($manifest[$file]['css'] ?? [] as $cssfile) {
                    $cssurls[] = new \moodle_url('/local/satsmail/svelte/build/' . $cssfile);
                }
            }
            foreach ($cssurls as $cssurl) {
                if ($this->page->requires->is_head_done()) {
                    // Head already written, add CSS using javascript.
                    $html .= \html_writer::script('(function() {
                        var doc = document.getElementsByTagName("head")[0];
                        var link = document.createElement("link");
                        link.rel = "stylesheet";
                        link.href = "' . $cssurl->out(false) . '";
                        doc.appendChild(link);
                    })();');
                } else {
                    $this->page->requires->css($cssurl);
                }
            }
        }

        $html .= \html_writer::tag('script', '', ['type' => 'module', 'src' => $jsurl]);

        return $html;
    }
}

