<?php

/**
 * Download a file
 *
 * @package    local
 * @subpackage hrimport
 * @copyright  &copy; 2017 Kineo Pacific {@link http://kineo.com.au}
 * @author     tri.le
 * @version    1.0
 */

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');

require_login();
// WR#333652: Restrict HR Import access to administrators.
require_capability('moodle/site:config', context_system::instance());

$filename = required_param('filename', PARAM_FILE);
$dir = $CFG->tempdir.'/hrimport/'.$filename;
if (!file_exists($dir)) {
    print_error('filenotexist');
}

if (strpos($CFG->wwwroot, 'https://') === 0) { //https sites - watch out for IE! KB812935 and KB316431
    header('Cache-Control: max-age=10');
    header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    header('Pragma: ');
} else { //normal http - prevent caching at all cost
    header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
    header('Expires: '. gmdate('D, d M Y H:i:s', 0) .' GMT');
    header('Pragma: no-cache');
}

$filetype = substr($filename, strpos($filename, '.')+1);
header('Content-Disposition: attachment; filename="'.$filename.'"');
if ($filetype=='zip') {
    $mime = 'application/zip, application/octet-stream';
} else {
    $mime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
}
readfile_accel($dir, $mime, false);
unlink($dir);