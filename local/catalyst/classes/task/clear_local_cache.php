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

namespace local_catalyst\task;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

defined('MOODLE_INTERNAL') || die();

/**
 * Clear local cache files if they are older than 24 hours
 *
 * @package local_catalyst
 */
class clear_local_cache extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('taskclearlocalcache', 'local_catalyst');
    }

    public function execute($force = false) {
        global $CFG;

        if (!empty($CFG->localcachedir)) {
            $paths = array_filter(glob($CFG->localcachedir . '/*-*-*-*-*', GLOB_ONLYDIR), 'is_dir');
            foreach ($paths as $path) {
                // 24 hours in seconds
                if (time() - filemtime($path) > 86400) {
                    // Delete folder contents then delete folder

                    $files = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                        RecursiveIteratorIterator::CHILD_FIRST);
                    foreach ($files as $file) {
                        if ($file->isDir()) {
                            rmdir($file->getRealPath());
                        } else {
                            unlink($file->getRealPath());
                        }
                    }

                    rmdir($path);
                }
            }
        }

        return true;
    }

}
