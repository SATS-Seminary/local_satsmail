<?php
/*
South African Theological Seminary
 */

namespace local_satsmail;

class exception extends \moodle_exception {
    /**
     * Constructor.
     *
     * @param string $errorcode Language string name.
     * @param mixed $a Language string parameters.
     * @param ?string $debuginfo Optional debugging information
     */
    public function __construct(string $errorcode, $a = null, ?string $debuginfo = null) {
        parent::__construct($errorcode, 'local_satsmail', '', $a, $debuginfo);
    }
}

