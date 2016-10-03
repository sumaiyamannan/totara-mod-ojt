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

/**
 * The local_catalyst 'sync file uploaded' event trigger.
 *
 * The fileinfo data will be contained within the 'other'
 * data attribute of the triggered event:
 *           'contextid' => $context->id,
 *           'component' => 'totara_sync',
 *           'filearea' => $element,
 *           'itemid' => $now,
 *           'filepath' => '/',
 *           'filename' => "{$element}-{$now}"
 *
 * @package    local_catalyst
 * @author     Eugene Venter <eugene@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_catalyst\event;
defined('MOODLE_INTERNAL') || die();

class sync_file_uploaded extends \core\event\base {
    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "Sync file '".$this->other['filename']."' uploaded.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('syncfileuploaded', 'local_catalyst');
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
}
