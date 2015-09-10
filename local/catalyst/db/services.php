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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$functions = array(
    'local_catalyst_totara_sync_upload_file' => array(
        'classname'   => 'local_catalyst',
        'methodname'  => 'totara_sync_upload',
        'classpath'   => 'local/catalyst/externallib.php',
        'description' => 'Upload Totara sync files.',
        'type'        => 'write',
        'capabilities'=> 'local/catalyst:totarasyncwsupload',
    )
);

// Define a pre-built service for the totarasync file upload
$services = array(
    'Totara sync file uploads' => array(
        'functions' => array ('local_catalyst_totara_sync_upload_file'),
        'requiredcapability' => 'local/catalyst:totarasyncwsupload',
        'restrictedusers' => 1,
        'enabled' => 0,
    )
);
