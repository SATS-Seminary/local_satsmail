<?php
/*
South African Theological Seminary
 */

namespace local_satsmail;

/**
 * @covers \local_satsmail\exception
 */
final class exception_test extends \basic_testcase {
    public function test_construct(): void {
        $exception = new exception('errortoomanyrecipients', 123, 'debug info');

        self::assertEquals('errortoomanyrecipients', $exception->errorcode);
        self::assertEquals('local_satsmail', $exception->module);
        self::assertEquals(123, $exception->a);
        self::assertEquals('', $exception->link);
        self::assertEquals('debug info', $exception->debuginfo);
    }
}

