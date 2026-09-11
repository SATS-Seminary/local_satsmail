<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Access to SATS Recorder recordings embedded in messages.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Access to SATS Recorder recordings embedded in messages.
 *
 * The compose editor runs in the system context, so tiny_satsrecorder stores
 * recordings made there against the system context, where recipients have no
 * enrolment and would otherwise be refused. A recording embedded in a message
 * is treated like an attachment of that message: whoever may see the message's
 * attachments may play it.
 */
class recording_access {
    /** @var int How many forwarding hops to follow from the owner's own messages. */
    const MAX_HOPS = 10;

    /**
     * Whether a user may play a recording because it was sent to them.
     *
     * Access only flows from people who already have it. A key that merely
     * appears in a message proves nothing — anyone who has seen a key could
     * paste it into a message to themselves — so only messages sent by someone
     * entitled to the recording count: its owner, or anyone it was in turn sent
     * to. That is what makes forwards work, since a forward is a new message
     * with no reference back to the original, while a user who never received
     * the recording cannot pass it on. Replies are also covered by the
     * reference check inside user::can_view_files().
     *
     * @param int $userid ID of the user asking to play the recording.
     * @param int $ownerid ID of the user who made the recording.
     * @param string $s3key The recording's S3 object key, exactly as stored.
     * @return bool
     */
    public static function user_can_view(int $userid, int $ownerid, string $s3key): bool {
        if (!settings::is_installed() || $s3key === '') {
            return false;
        }

        $user = user::get($userid);
        if ($user->deleted) {
            return false;
        }

        // The key is URL-encoded in the embedded serve.php URL, but older content
        // and hand-edited HTML may carry it in other forms.
        $variants = array_values(array_unique([rawurlencode($s3key), urlencode($s3key), $s3key]));

        // Breadth-first from the owner: each round looks at messages embedding the
        // key sent by users entitled to it, and entitles those messages' recipients.
        $entitled = [$ownerid => true];
        $senders = [$ownerid];
        $seen = [];
        for ($hop = 0; $senders && $hop <= self::MAX_HOPS; $hop++) {
            $next = [];
            foreach (self::messages_embedding($senders, $variants) as $message) {
                if (isset($seen[$message->id])) {
                    continue;
                }
                $seen[$message->id] = true;
                if ($user->can_view_files($message)) {
                    return true;
                }
                foreach ($message->recipients() as $recipient) {
                    if (!isset($entitled[$recipient->id]) && $recipient->can_view_message($message)) {
                        $entitled[$recipient->id] = true;
                        $next[] = $recipient->id;
                    }
                }
            }
            $senders = $next;
        }

        return false;
    }

    /**
     * Sent messages from the given users that embed the recording.
     *
     * @param int[] $senderids IDs of the senders.
     * @param string[] $variants Encodings of the key to look for.
     * @return message[]
     */
    private static function messages_embedding(array $senderids, array $variants): array {
        global $DB;

        // Pre-filter in SQL, which the userid index narrows cheaply; the content
        // match is confirmed exactly below.
        [$sendersql, $params] = $DB->get_in_or_equal($senderids, SQL_PARAMS_NAMED, 'sender');
        $params['role'] = message::ROLE_FROM;
        $likes = [];
        foreach ($variants as $i => $variant) {
            $likes[] = $DB->sql_like('m.content', ":key$i");
            $params["key$i"] = '%key=' . $DB->sql_like_escape($variant) . '%';
        }
        $sql = 'SELECT m.id'
            . ' FROM {local_satsmail_message_users} mu'
            . ' JOIN {local_satsmail_messages} m ON m.id = mu.messageid'
            . " WHERE mu.userid $sendersql AND mu.role = :role AND mu.draft = 0"
            . ' AND (' . implode(' OR ', $likes) . ')';
        $messageids = array_keys($DB->get_records_sql($sql, $params));

        return array_filter(
            message::get_many($messageids),
            fn(message $message) => self::embeds($message->content, $variants)
        );
    }

    /**
     * Whether content embeds a serve.php URL for exactly this key.
     *
     * A plain substring test would also match a longer key that merely starts
     * with this one, so the key must be followed by the end of the parameter.
     *
     * @param string $content Message content.
     * @param string[] $variants Encodings of the key to look for.
     * @return bool
     */
    private static function embeds(string $content, array $variants): bool {
        foreach ($variants as $variant) {
            $pattern = '/[?&;]key=' . preg_quote($variant, '/') . '(?=["\'&#<\s]|$)/';
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        return false;
    }
}
