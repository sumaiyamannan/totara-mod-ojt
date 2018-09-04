<?php
/**
 * Defines the version of this module
 *
 * @package   local_clustercache
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version  = 2014053100;
$plugin->requires = 2010031900;  // Requires this Moodle version
$plugin->component = 'local_clustercache'; // Full name of the plugin (used for diagnostics)
$plugin->cron     = 0;           // Period for cron to check this module (secs)
