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
 * Email bounce checker - check a configured mailbox for bounce messages
 * Update Moodle user bounce counts and delete mail message
 * Delete old bounce messages
 * Expunge
 *
 * @package local_bouncechecker
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Bounce checker';
$string['enabled'] = 'Bounce checker enabled';
$string['mailserver'] = 'Mail server';
$string['mailserverconfig'] = 'The mail server to check for bounce emails';
$string['mailusername'] = 'Mail username';
$string['mailpassword'] = 'Mail password';
$string['cleanupdays'] = 'Clean up any old mail after (days)';
$string['cleanupdaysconfig'] = 'Delete mail older than the specified amount of days. Set to 0 to disable cleanup.';
