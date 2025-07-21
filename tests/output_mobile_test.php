<?php
/*
South African Theological Seminary
 */

namespace local_satsmail;

/**
 * @covers \local_satsmail\output\mobile
 */
final class output_mobile_test extends test\testcase {
    public function test_init(): void {
        global $CFG;

        $generator = self::getDataGenerator();
        $course = new course($generator->create_course());
        $user1 = new user($generator->create_user());
        $user2 = new user($generator->create_user());
        $generator->enrol_user($user1->id, $course->id);
        self::setUser($user1->id);

        // User with courses.
        self::assertEquals(
            ['javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-init.js")],
            output\mobile::init(),
        );

        // User with no courses.
        self::setUser($user2->id);
        self::assertEquals(['disabled' => true], output\mobile::init());

        // Not installed.
        unset_config('version', 'local_satsmail');
        self::setUser($user1->id);
        self::assertEquals(['disabled' => true], output\mobile::init());
    }

    public function test_view(): void {
        global $CFG;

        self::assertEquals(
            [
                'templates' => [
                    [
                        'id' => 'main',
                        'html' => '<core-iframe src="' . $CFG->wwwroot . '/local/satsmail/view.php?t=inbox&m=123"></core-iframe>',
                    ],
                ],
                'javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-view.js"),
            ],
            output\mobile::view(['t' => 'inbox', 'm' => 123])
        );
    }
}

