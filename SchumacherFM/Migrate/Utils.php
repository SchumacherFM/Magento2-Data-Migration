<?php

/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */

namespace SchumacherFM\Migrate;

class Utils
{

    public static function splitLines($content) {
        preg_match_all("/[^\n\r]+[\r\n]*/", $content, $matches);

        return $matches[0];
    }
}
