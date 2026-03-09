<?php
/*
South African Theological Seminary
 */

namespace local_satsmail;

class user {
    /** @var int User ID. */
    public int $id;

    /** @var bool Deleted status. */
    public bool $deleted;

    /** @var string First name. */
    public string $firstname = '';

    /** @var string Last name. */
    public string $lastname = '';

    /** @var string Email address. */
    public string $email = '';

    /** @var int Picture file ID. */
    public int $picture = 0;

    /** @var string Picture description. */
    public string $imagealt = '';

    /** @var string Phonetic spelling of first name. */
    public string $firstnamephonetic = '';

    /** @var string Phonetic spelling of last name. */
    public string $lastnamephonetic = '';

    /** @var string Middle name. */
    public string $middlename = '';

    /** @var string Alternate name. */
    public string $alternatename = '';

    /**
     * Constructs a user instance from a database record.
     *
     * @param \stdClass $record Database record from table user.
     */
    public function __construct(\stdClass $record) {
        $this->id = (int) $record->id;
        $this->deleted = !empty($record->deleted);
        if (!$this->deleted) {
            $this->firstname = $record->firstname;
            $this->lastname = $record->lastname;
            $this->email = $record->email;
            $this->picture = (int) $record->picture;
            $this->imagealt = $record->imagealt ?? '';
            $this->firstnamephonetic = $record->firstnamephonetic ?? '';
            $this->lastnamephonetic = $record->lastnamephonetic ?? '';
            $this->middlename = $record->middlename ?? '';
            $this->alternatename = $record->alternatename ?? '';
        }
    }

    /**
     * Returns the current logged in user.
     *
     * @return ?self The current or null if not logged in or is guest.
     */
    public static function current(): ?self {
        global $USER;

        if (!isloggedin() || isguestuser() || \core_user::awaiting_action()) {
            return null;
        }

        $user = new self($USER);

        return $user;
    }

    /**
     * Gets a user from the database.
     * An empty user is returned if the user is missing or deleted.
     *
     * @param int $id ID of the user to get.
     * @return self
     */
    public static function get(int $id): self {
        $users = self::get_many([$id]);

        return $users[$id];
    }

    /**
     * Gets multiple users from the database.
     * Empty users are returned for missing and deleted users.
     *
     * @param int[] $ids IDs of the users to get.
     * @return self[] Array of users indexed by ID.
     */
    public static function get_many(array $ids): array {
        global $CFG, $DB;

        if (!$ids) {
            return [];
        }

        [$sqlid, $params] = $DB->get_in_or_equal($ids, SQL_PARAMS_NAMED, 'userid');
        $select = "id $sqlid AND deleted = 0 AND id <> :guestid";
        $params['guestid'] = $CFG->siteguest;
        $fields = implode(',', \core_user\fields::get_picture_fields());
        $records = $DB->get_records_select('user', $select, $params, '', $fields);

        foreach ($ids as $id) {
            $users[$id] = new self($records[$id] ?? (object) ['id' => $id, 'deleted' => 1]);
        }

        return $users;
    }

    /**
     * Returns whether the user can edit the message.
     *
     * @param message $message Message.
     * @return bool
     */
    public function can_edit_message(message $message): bool {
        return $message->draft &&
            $this->id == $message->sender()->id &&
            in_array($message->deleted($this), [message::NOT_DELETED, message::DELETED]) &&
            $this->can_use_mail($message->course);
    }

    /**
     * Returns whether the user can use mail in a course.
     *
     * @param course $course Course.
     * @return bool
     */
    public function can_use_mail(course $course) {
        return array_key_exists($course->id, course::get_by_user($this));
    }

    /**
     * Returns whether the user can view the attachments of a message.
     *
     * @param message $message Message.
     * @return bool
     */
    public function can_view_files(message $message): bool {
        if ($this->can_view_message($message)) {
            return true;
        }
        foreach ($message->get_references(true) as $reference) {
            if ($this->can_view_message($reference)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns whether the user can view a group.
     *
     * @param course $course Course.
     * @param int $groupid ID of the group or 0 for all groups.
     * @return bool
     */
    public function can_view_group(course $course, int $groupid): bool {
        if (!$this->can_use_mail($course)) {
            return false;
        } else if ($course->groupmode == NOGROUPS) {
            return $groupid == 0;
        } else {
            return array_key_exists($groupid, $course->get_viewable_groups($this));
        }
    }

    /**
     * Returns whether the user can view a message.
     *
     * Viewing is based on participation (sender or recipient), not enrollment.
     * This allows users to read their mail history even after enrollment expires.
     *
     * @param message $message Message.
     * @return bool
     */
    public function can_view_message(message $message): bool {
        return ($message->sender()->id == $this->id || !$message->draft && $message->has_recipient($this)) &&
            in_array($message->deleted($this), [message::NOT_DELETED, message::DELETED]);
    }

    /**
     * Full name of the user.
     *
     * @return string
     */
    public function fullname(): string {
        return $this->deleted ? get_string('deleteduser', 'local_satsmail') : fullname((object) (array) $this);
    }

    /**
     * URL of the picture of the user.
     *
     * @return string
     */
    public function picture_url(): ?string {
        global $PAGE;

        if ($this->deleted) {
            return '';
        }

        $userpicture = new \user_picture((object) (array) $this);
        $url = $userpicture->get_url($PAGE)->out(false);
        $defaulturl = $PAGE->get_renderer('core')->image_url('u/f2')->out(false);

        return $url == $defaulturl ? '' : $url;
    }

    /**
     * URL of the profile of the user in a course.
     *
     * @param course $course Course.
     * @return string
     */
    public function profile_url(course $course): string {
        if ($this->deleted) {
            return '';
        }

        $params = ['id' => $this->id];
        if ($course) {
            $params['course'] = $course->id;
        }
        $url = new \moodle_url('/user/view.php', $params);

        return $url->out(false);
    }

    /**
     * Sort order of the user.
     *
     * @return string
     */
    public function sortorder(): string {
        return sprintf("%d\n%s\n%s\n%010d", $this->deleted, $this->lastname, $this->firstname, $this->id);
    }

    /**
     * Returns whether the given user is a member of the configured CC cohort.
     *
     * @param int $userid User ID to check.
     * @return bool
     */
    public static function is_cc_cohort_member(int $userid): bool {
        global $DB;

        $cohortid = (int) get_config('local_satsmail', 'cccohortid');
        if (!$cohortid) {
            return false;
        }

        return $DB->record_exists('cohort_members', ['cohortid' => $cohortid, 'userid' => $userid]);
    }

    /**
     * Searches members of the configured CC cohort, optionally filtered by name.
     * Excludes the given user (the searcher) from results.
     *
     * @param int $excludeuserid User ID to exclude from results.
     * @param string $fullname Optional name filter.
     * @param int[] $include Optional list of user IDs to restrict results to.
     * @param int $offset Skip this number of users.
     * @param int $limit Maximum number of users, 0 means no limit.
     * @return self[] Found users, indexed by ID.
     */
    public static function search_cc_cohort(int $excludeuserid, string $fullname = '', array $include = [], int $offset = 0, int $limit = 0): array {
        global $CFG, $DB;

        $cohortid = (int) get_config('local_satsmail', 'cccohortid');
        if (!$cohortid) {
            return [];
        }

        $fields = implode(',', array_map(fn($f) => "u.$f", \core_user\fields::get_picture_fields()));
        $sql = "SELECT $fields
                  FROM {user} u
                  JOIN {cohort_members} cm ON cm.userid = u.id
                 WHERE cm.cohortid = :cohortid
                   AND u.deleted = 0
                   AND u.id <> :excludeuserid
                   AND u.id <> :guestid";
        $params = [
            'cohortid' => $cohortid,
            'excludeuserid' => $excludeuserid,
            'guestid' => $CFG->siteguest,
        ];

        if ($fullname !== '') {
            $fullnamefield = $DB->sql_fullname('u.firstname', 'u.lastname');
            $sql .= ' AND ' . $DB->sql_like($fullnamefield, ':fullname', false, false);
            $params['fullname'] = '%' . $DB->sql_like_escape($fullname) . '%';
        }

        if ($include) {
            [$includesql, $includeparams] = $DB->get_in_or_equal($include, SQL_PARAMS_NAMED, 'id');
            $sql .= ' AND u.id ' . $includesql;
            $params = array_merge($params, $includeparams);
        }

        [$sort, $sortparams] = users_order_by_sql('u');
        $sql .= ' ORDER BY ' . $sort;
        $params = array_merge($params, $sortparams);

        $records = $DB->get_records_sql($sql, $params, $offset, $limit);

        $users = [];
        foreach ($records as $record) {
            $users[$record->id] = new self($record);
        }

        return $users;
    }
}

