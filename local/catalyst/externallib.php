<?php

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
 * Catalyst-specific webservices
 *
 * @package    local_catalyst
 * @author     Eugene Venter <eugene@catalyst.net.nz>
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_catalyst extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function totara_sync_upload_parameters() {
        return new external_function_parameters(
            array(
                'element' => new external_value(PARAM_TEXT, 'The sync element shortname.', VALUE_REQUIRED),
                'filecontent' => new external_value(PARAM_RAW, 'The content of the file.', VALUE_REQUIRED)
            )
        );
    }

    /**
     * Uploads a file for an element to where Totara sync expects it
     * @param string $element the element name e.g 'org', 'pos', 'user'
     * @param string $filecontent the file content
     * @return string 'success' on succesful upload
     */
    public static function totara_sync_upload($element, $filecontent) {
        global $USER;

        // Parameter validation
        $params = self::validate_parameters(self::totara_sync_upload_parameters(),
                array('element' => $element, 'filecontent' => $filecontent));

        // Context validation
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/catalyst:totarasyncwsupload', $context);

        // Do the deed and upload the file to the dumb place totarasync expects it.
        $fs = get_file_storage();

        // Prepare file record object to what Totara sync expects
        $now = time();
        $fileinfo = array(
            'contextid' => $context->id,
            'component' => 'totara_sync',
            'filearea' => $element,
            'itemid' => $now,
            'filepath' => '/',
            'filename' => "{$element}-{$now}"
        );

        // Delete all current area files
        $fs->delete_area_files($context->id, 'totara_sync', $element);  // or do we want to keep an archive (move the files)?

        // Create file
        $fs->create_file_from_string($fileinfo, base64_decode($filecontent));

        // Set the itemid totara_sync expects
        set_config("sync_{$element}_itemid", $now, 'totara_sync');

		// Trigger a 'sync file uploaded' event.
		$event = \local_catalyst\event\sync_file_uploaded::create(array(
			'context' => $context,
			'other' => $fileinfo
		));
		$event->trigger();

        return 'success';
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function totara_sync_upload_returns() {
        return new external_value(PARAM_TEXT, '"success" upon successful upload');
    }
}
