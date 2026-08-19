<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * CLI script that generates random messages for testing.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/*
South African Theological Seminaryy
 */
namespace local_satsmail;

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/clilib.php');

/** @var array Emojis used in random text. */
const EMOJIS = ['😀', '😛', '😱', '👍'];
/** @var array Consonants used in random words. */
const CONSONANTS = ['b', 'c', 'ç', 'd', 'f', 'g', 'h', 'j', 'k', 'l', 'm', 'n', 'p', 'q', 'r', 's', 't', 'v', 'x', 'y', 'z'];
/** @var array Vowels used in random words. */
const VOWELS = ['a', 'e', 'i', 'o', 'u'];
/** @var int Maximum number of distinct random words. */
const MAX_WORDS = 10000;
/** @var int Maximum number of distinct random sentences. */
const MAX_SENTENCES = 10000;

/** @var float Frequency of emojis in random text. */
const EMOJI_FREQ = 0.05;
/** @var float Frequency of commas in random sentences. */
const COMMA_FREQ = 0.1;
/** @var float Frequency of question marks in random sentences. */
const QUESTION_FREQ = 0.2;
/** @var float Frequency of dashes in random sentences. */
const DASH_FREQ = 0.1;
/** @var float Expected number of syllables per random word. */
const SYLLABES_PER_WORD_EX = 2;
/** @var float Standard deviation of syllables per random word. */
const SYLLABES_PER_WORD_SD = 0.5;
/** @var float Expected number of words per random sentence. */
const WORD_PER_SENTENCE_EX = 8;
/** @var float Standard deviation of words per random sentence. */
const WORD_PER_SENTENCE_SD = 3;
/** @var float Expected number of sentences per random paragraph. */
const SENTENCES_PER_PARAGRAPH_EX = 5;
/** @var float Standard deviation of sentences per random paragraph. */
const SENTENCES_PER_PARAGRAPH_SD = 3;
/** @var float Expected number of paragraphs per random message. */
const PARAGRAPHS_PER_MESSAGE_EX = 3;
/** @var float Standard deviation of paragraphs per random message. */
const PARAGRAPHS_PER_MESSAGE_SD = 1;

/** @var int Default number of generated messages per user and course. */
const MESSAGES_PER_USER_PER_COURSE = 25;
/** @var float Expected number of generated labels per user. */
const LABELS_PER_USER_EX = 3;
/** @var float Standard deviation of generated labels per user. */
const LABELS_PER_USER_SD = 2;
/** @var float Frequency of replies among generated messages. */
const REPLY_FREQ = 0.7;
/** @var float Frequency of forwards among generated messages. */
const FORWARD_FREQ = 0.1;
/** @var float Frequency of drafts among generated messages. */
const DRAFT_FREQ = 0.1;
/** @var float Expected number of direct recipients per generated message. */
const TO_RECIPIENTS_EX = 1;
/** @var float Standard deviation of direct recipients per generated message. */
const TO_RECIPIENTS_SD = 2;
/** @var float Expected number of carbon copy recipients per generated message. */
const CC_RECIPIENTS_EX = 0;
/** @var float Standard deviation of carbon copy recipients per generated message. */
const CC_RECIPIENTS_SD = 2;
/** @var float Expected number of blind carbon copy recipients per generated message. */
const BCC_RECIPIENTS_EX = -10;
/** @var float Standard deviation of blind carbon copy recipients per generated message. */
const BCC_RECIPIENTS_SD = 10;
/** @var float Expected number of attachments per generated message. */
const ATTACHMENTS_EX = -1;
/** @var float Standard deviation of attachments per generated message. */
const ATTACHMENTS_SD = 1;
/** @var float Frequency of reply-to-all among generated replies. */
const REPLY_ALL_FREQ = 0.5;
/** @var float Exponent of the frequency of unread generated messages. */
const UNREAD_FREQ_EXP = 4;
/** @var float Frequency of starred generated messages. */
const STARRED_FREQ = 0.2;
/** @var float Frequency of deleted generated messages. */
const DELETED_FREQ = 0.1;
/** @var float Frequency of generated messages with deleted content. */
const DELETED_CONTENT_FREQ = 0.05;
/** @var float Expected number of labels per generated message. */
const MESSAGE_LABEL_EX = 0;
/** @var float Standard deviation of labels per generated message. */
const MESSAGE_LABEL_SD = 1;

set_debugging(DEBUG_DEVELOPER, true);

/**
 * Generates random courses, messages and labels.
 */
function main() {
    global $CFG, $DB;

    raise_memory_limit(MEMORY_HUGE);

    // Run script as an admin user, to be able to use file draft areas.
    \core\cron::setup_user();

    $countperuser = MESSAGES_PER_USER_PER_COURSE;
    $countperuser = (int) cli_input("Messages per user per course? [$countperuser]", $countperuser);
    if ($countperuser <= 0) {
        cli_error('Invalid number of messages.');
    }
    cli_writeln('');

    $admin = null;
    $adminname = trim(cli_input("Name of a user that will receive all mail as BCC [none]", ''));
    if ($adminname) {
        $admin = \core_user::get_user_by_username($adminname);
        if (!$admin) {
            cli_error('User not found.');
        }
        $admin = new user($admin);
    }
    cli_writeln('');

    $confirm = cli_input('ALL EXISTING MAIL DATA WILL BE DELETED! Type "OK" to continue.');
    if ($confirm != 'OK') {
        cli_error('Canceled.');
    }
    cli_writeln('');

    $starttime = time();

    $fs = get_file_storage();
    $courses = [];
    foreach (get_courses('all', 'c.sortorder') as $record) {
        if ($record->id != SITEID) {
            $courses[$record->id] = new course($record);
        }
    }

    delete_messages($courses);
    generate_user_labels();
    foreach ($courses as $course) {
        generate_course_messages($fs, $course, $admin, $countperuser);
    }

    $seconds = (int) (time() - $starttime);
    cli_writeln("\n\nFinished in $seconds seconds.");
}

/**
 * Deletes all the messages of the given courses.
 *
 * @param array $courses Courses to delete messages from.
 */
function delete_messages(array $courses) {
    global $DB;

    foreach ($courses as $course) {
        print_progress("Deleting course mail", count($courses));

        message::delete_course_data($course->get_context());
    }
}

/**
 * Adds a random number of attachments to a message.
 *
 * @param \file_storage $fs File storage.
 * @param message_data $data Data of the message.
 */
function add_random_attachments(\file_storage $fs, message_data $data) {
    global $USER;

    $context = \context_user::instance($USER->id);

    $filenames = [];

    $count = random_count(0, ATTACHMENTS_EX, ATTACHMENTS_SD);

    for ($i = 0; $i < $count; $i++) {
        $filename = '';
        while (!$filename || in_array($filename, $filenames)) {
            $filename = random_word() . '.html';
        }
        $filenames[] = $filename;
        $filerecord = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $data->draftitemid,
            'filepath' => '/',
            'filename' => $filename,
            'timecreated' => (int) $data->time,
            'timemodified' => (int) $data->time,
            'userid' => $data->sender->id,
            'mimetype' => 'text/html',
        ];
        $fs->create_file_from_string($filerecord, random_content());
    }
}

/**
 * Adds a random set of recipients to a message.
 *
 * @param message_data $data Data of the message.
 * @param array $users Users that can be added as recipients.
 */
function add_random_recipients(message_data $data, array $users): void {
    $counts = new \stdClass();
    $maxcount = count($users) - 1;
    $counts->to = min($maxcount, random_count(1, TO_RECIPIENTS_EX, TO_RECIPIENTS_SD));
    $maxcount -= $counts->to;
    $counts->cc = min($maxcount, random_count(0, CC_RECIPIENTS_EX, CC_RECIPIENTS_SD));
    $maxcount -= $counts->cc;
    $counts->bcc = min($maxcount, random_count(0, BCC_RECIPIENTS_EX, BCC_RECIPIENTS_SD));

    $isparticipant = [$data->sender->id => true];
    foreach ($counts as $rolename => $count) {
        foreach ($data->$rolename as $recipient) {
            $isparticipant[$recipient->id] = true;
        }
    }

    foreach ($counts as $rolename => $count) {
        while ($count > 0) {
            $user = random_item($users);
            if (empty($isparticipant[$user->id])) {
                $data->{$rolename}[] = $user;
                $isparticipant[$user->id] = true;
                $count--;
            }
        }
    }
}

/**
 * Generates random messages in a course.
 *
 * @param \file_storage $fs File storage.
 * @param course $course Course to generate messages in.
 * @param ?user $admin Admin user, if messages are also sent by the admin.
 * @param int $countperuser Number of messages generated per user.
 */
function generate_course_messages(\file_storage $fs, course $course, ?user $admin, int $countperuser): void {
    global $DB;

    $users = user::get_many(array_keys(get_enrolled_users($course->get_context())));
    if (count($users) < 2) {
        return;
    }

    $count = $countperuser * count($users);
    $endtime = time();
    $starttime = $endtime - 365 * 86400;
    $sentmessages = [];

    for ($i = 0; $i < $count; $i++) {
        print_progress("Generating messages for course " . $course->shortname, $count);
        $transaction = $DB->start_delegated_transaction();
        $time = (int) (($endtime - $starttime) * $i / $count + $starttime);
        if ($i > 0 && random_bool(REPLY_FREQ)) {
            $data = generate_random_reply($fs, random_item($sentmessages), $time);
        } else if ($i > 0 && random_bool(FORWARD_FREQ / (1 - REPLY_FREQ))) {
            $data = generate_random_forward($fs, random_item($sentmessages), $users, $time);
        } else {
            $data = generate_random_message($fs, $course, $users, $time);
        }
        if ($admin && $data->sender->id != $admin->id) {
            $data->bcc[] = $admin;
        }
        $message = message::create($data);
        if ($i == 0 || !random_bool(DRAFT_FREQ)) {
            $message->send($time);
            $sentmessages[] = $message;
            // Only reply and forward recent messages.
            $countperweek = (int) ($count / 52);
            if (count($sentmessages) > $countperweek * 2) {
                $sentmessages = array_slice($sentmessages, $countperweek);
            }
        }
        set_random_unread($message, $starttime, $endtime);
        set_random_starred($message);
        set_random_labels($message);
        set_random_deleted($message);
        $transaction->allow_commit();
    }
}

/**
 * Generates the data of a random forward of a message.
 *
 * @param \file_storage $fs File storage.
 * @param message $message Message to forward.
 * @param array $users Users that can be added as recipients.
 * @param int $time Time of the forward.
 * @return message_data Data of the generated forward.
 */
function generate_random_forward(\file_storage $fs, message $message, array $users, int $time): message_data {
    $sender = random_item($message->recipients(message::ROLE_TO, message::ROLE_CC));
    $data = message_data::forward($message, $sender);
    $data->time = $time;

    add_random_recipients($data, $users);

    return $data;
}

/**
 * Generates the data of a random message in a course.
 *
 * @param \file_storage $fs File storage.
 * @param course $course Course of the message.
 * @param array $users Users that can be added as recipients.
 * @param int $time Time of the message.
 * @return message_data Data of the generated message.
 */
function generate_random_message(\file_storage $fs, course $course, array $users, int $time): message_data {
    $sender = random_item($users);
    $data = message_data::new($course, $sender);
    $data->subject = random_sentence();
    $data->content = random_content();
    $data->time = $time;

    add_random_attachments($fs, $data);
    add_random_recipients($data, $users);

    return $data;
}

/**
 * Generates the data of a random reply to a message.
 *
 * @param \file_storage $fs File storage.
 * @param message $message Message to reply to.
 * @param int $time Time of the reply.
 * @return message_data Data of the generated reply.
 */
function generate_random_reply(\file_storage $fs, message $message, int $time): message_data {
    $sender = random_item($message->recipients(message::ROLE_TO, message::ROLE_CC));
    $all = random_bool(REPLY_ALL_FREQ);
    $data = message_data::reply($message, $sender, $all);
    $data->content = random_content();
    $data->time = $time;

    add_random_attachments($fs, $data);

    return $data;
}

/**
 * Generates random labels for all the users of the site.
 */
function generate_user_labels() {
    global $CFG, $DB;

    $records = $DB->get_records_select('user', 'deleted = 0 AND id <> ?', [$CFG->siteguest], '', 'id');
    $users = user::get_many(array_keys($records));

    foreach ($users as $user) {
        print_progress('Generating user labels', count($users));

        foreach (label::get_by_user($user) as $label) {
            $label->delete();
        }
        $n = random_count(0, LABELS_PER_USER_EX, LABELS_PER_USER_SD);
        for ($i = 0; $i < $n; $i++) {
            $name = random_word(true);
            $color = random_item(label::COLORS);
            label::create($user, $name, $color);
        }
    }
}

/**
 * Prints the progress of the generation.
 *
 * @param string $message Message to print.
 * @param int $total Total number of steps.
 */
function print_progress(string $message = '', int $total = 0) {
    static $prevmessage = '';
    static $value = 0;
    static $printtime = 0;

    if ($message != $prevmessage) {
        if (strlen($prevmessage)) {
            cli_writeln('');
        }
        $prevmessage = $message;
        $value = 0;
        $printtime = 0;
    }

    $value++;

    if (strlen($message) && ($value == $total || time() - $printtime > 0.5)) {
        $message = "\r$message... ";
        if ($total > 0) {
            $message .= "$value/$total ";
        }
        cli_write($message);
        $printtime = time();
    }
}

/**
 * Returns a random boolean with the given frequency of true values.
 *
 * @param float $truefreq Frequency of true values, between 0 and 1.
 * @return bool Random boolean.
 */
function random_bool(float $truefreq): bool {
    return rand() / getrandmax() < $truefreq;
}

/**
 * Returns random HTML content for a message.
 *
 * @return string Random content.
 */
function random_content(): string {
    $s = '';
    $n = random_count(1, PARAGRAPHS_PER_MESSAGE_EX, PARAGRAPHS_PER_MESSAGE_SD);
    for ($i = 0; $i < $n; $i++) {
        $s .= "\n" . random_paragraph();
    }
    return $s;
}

/**
 * Returns a random count with the given normal distribution.
 *
 * @param int $min Minimum value.
 * @param float $ex Expected value.
 * @param float $sd Standard deviation.
 * @return int Random count.
 */
function random_count(int $min, float $ex, float $sd): int {
    $x = rand() / getrandmax();
    $y = rand() / getrandmax();
    $r = sqrt(-2 * log($x)) * cos(2 * pi() * $y) * $sd + $ex;
    return max($min, (int) round($r));
}

/**
 * Returns a random item of an array.
 *
 * @param array $items Items to choose from.
 * @return mixed Random item.
 */
function random_item(array $items) {
    return $items[array_rand($items)];
}

/**
 * Returns a random paragraph of text.
 *
 * @return string Random paragraph.
 */
function random_paragraph(): string {
    $s = '<p>' . random_sentence(true);
    $n = random_count(1, SENTENCES_PER_PARAGRAPH_EX, SENTENCES_PER_PARAGRAPH_SD) - 1;
    for ($i = 0; $i < $n; $i++) {
        $s .= ' ' . random_sentence(true);
    }
    $s .= '</p>';
    return $s;
}

/**
 * Returns a random sentence of text.
 *
 * @param bool $period Terminate the sentence with a period.
 * @return string Random sentence.
 */
function random_sentence($period = false): string {
    if (random_bool(EMOJI_FREQ)) {
        return random_item(EMOJIS);
    }

    static $sentences = [];
    if (count($sentences) == MAX_SENTENCES) {
        $s = random_item($sentences);
    } else {
        $s = random_word(true);
        $n = random_count(1, WORD_PER_SENTENCE_EX, WORD_PER_SENTENCE_SD) - 1;

        for ($i = 0; $i < $n; $i++) {
            if (random_bool(COMMA_FREQ)) {
                $s .= ',';
            }
            $s .= ' ' . random_word();
        }

        $sentences[] = $s;
    }

    if ($period) {
        if (random_bool(QUESTION_FREQ)) {
            $s .= '?';
        } else {
            $s .= '.';
        }
    }

    return $s;
}

/**
 * Returns a random word.
 *
 * @param bool $capitalize Capitalize the first letter of the word.
 * @return string Random word.
 */
function random_word($capitalize = false): string {
    static $words = [];

    if (count($words) == MAX_WORDS) {
        $s = random_item($words);
    } else {
        $s = '';
        $n = random_count(1, SYLLABES_PER_WORD_EX, SYLLABES_PER_WORD_SD);

        for ($i = 0; $i < $n; $i++) {
            $c = random_item(CONSONANTS);
            $s .= $c . random_item(VOWELS);
            if ($i < $n - 1 && random_bool(DASH_FREQ)) {
                $s .= '-';
            }
        }

        $words[] = $s;
    }

    if ($capitalize) {
        $s = mb_strtoupper(mb_substr($s, 0, 1)) . mb_substr($s, 1);
    }

    return $s;
}

/**
 * Sets a random deleted status for the users of a message.
 *
 * @param message $message Message to update.
 */
function set_random_deleted(message $message): void {
    if (!$message->draft) {
        if (random_bool(DELETED_FREQ)) {
            $message->set_deleted($message->sender(), message::DELETED);
        }
        foreach ($message->recipients() as $user) {
            if (random_bool(DELETED_FREQ)) {
                $message->set_deleted($user, message::DELETED);
            }
        }
        if (random_bool(DELETED_CONTENT_FREQ)) {
            $message->set_deleted($message->sender(), message::DELETED_CONTENT);
        }
    }
}

/**
 * Sets random labels for the users of a message.
 *
 * @param message $message Message to update.
 */
function set_random_labels(message $message): void {
    $users = array_merge([$message->sender()], $message->recipients());
    foreach ($users as $user) {
        if (!$message->draft || $user->id == $message->sender()->id) {
            $labels = label::get_by_user($user);
            shuffle($labels);
            $count = random_count(0, MESSAGE_LABEL_EX, MESSAGE_LABEL_SD);
            $message->set_labels($user, array_slice($labels, 0, $count));
        }
    }
}

/**
 * Sets a random starred status for the users of a message.
 *
 * @param message $message Message to update.
 */
function set_random_starred(message $message): void {
    $message->set_starred($message->sender(), random_bool(STARRED_FREQ));
    if (!$message->draft) {
        foreach ($message->recipients() as $user) {
            $message->set_starred($user, random_bool(STARRED_FREQ));
        }
    }
}

/**
 * Sets a random unread status and time for the users of a message.
 *
 * @param message $message Message to update.
 * @param int $starttime Start of the time range.
 * @param int $endtime End of the time range.
 */
function set_random_unread(message $message, int $starttime, int $endtime): void {
    if (!$message->draft) {
        $freq = pow(($message->time - $starttime) / ($endtime - $starttime), UNREAD_FREQ_EXP);
        foreach ($message->recipients() as $user) {
            $message->set_unread($user, random_bool($freq));
        }
    }
}

main();
