<?php
/*
South African Theological Seminary
 */

 namespace local_satsmail;

/**
 * Event observer for local_satsmail.
 */
class observer {
    /**
     * Triggered via course_deleted event.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        message::delete_course_data($event->get_context());
    }
}

