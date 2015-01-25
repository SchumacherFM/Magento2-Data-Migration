<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */


namespace SchumacherFM\Migrate;

/**
 * Manager of errors that occur during fixing.
 *
 */
class ErrorsManager
{
    const ERROR_TYPE_EXCEPTION = 1;
    const ERROR_TYPE_LINT = 2;

    /**
     * Errors.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Get all reported errors.
     *
     * @return array
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Check if no errors was reported.
     *
     * @return bool
     */
    public function isEmpty() {
        return empty($this->errors);
    }

    /**
     * Report error.
     *
     * @param int $type error type
     * @param string $filepath file, on which error occurs
     * @param string $message description of error
     */
    public function report($type, $filepath, $message) {
        $this->errors[] = [
            'type' => $type,
            'filepath' => $filepath,
            'message' => $message,
        ];
    }
}
