<?php
/*
 * (c) Cyrill Schumacher <cyrill@schumacher.fm>
 *
 * This source file is subject to the OSL-30 that is bundled
 * with this source code in the file LICENSE.
 */


namespace SchumacherFM\Migrate;

/**
 * @author Cyrill Schumacher <cyrill@schumacher.fm>
 */
interface MigratorInterface
{

    /**
     * @return int 0 = success any other int = error
     */
    public function migrate();
}
