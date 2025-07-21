<?php
/*
South African Theological Seminary
 */

namespace local_satsmail;

/**
 * @covers \local_satsmail\observer
 */
final class observer_test extends test\testcase {
    public function test_course_deleted(): void {
        [$users, $messages] = self::generate_random_data(true);
        $course = $messages[0]->course;
        $context = $course->get_context();

        $fs = get_file_storage();

        delete_course($course->id, false);

        self::assert_record_count(0, 'messages', ['courseid' => $course->id]);
        self::assert_record_count(0, 'message_users', ['courseid' => $course->id]);
        self::assert_record_count(0, 'message_labels', ['courseid' => $course->id]);
        foreach ($messages as $message) {
            if ($message->course->id == $course->id) {
                self::assert_record_count(0, 'message_refs', ['messageid' => $message->id]);
                self::assert_record_count(0, 'message_refs', ['reference' => $message->id]);
            } else {
                self::assert_message($message);
            }
        }
        self::assertEmpty($fs->get_area_files($context->id, 'local_satsmail', 'message'));
    }
}

