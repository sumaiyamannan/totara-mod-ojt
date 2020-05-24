<?php

/**
 * Comment
 *
 * @package    package
 * @subpackage sub_package
 * @copyright  &copy; 2019 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);

require_once('../../../config.php');

require_once($CFG->dirroot . '/mod/ojt/lib.php');
require_once($CFG->dirroot . '/mod/ojt/locallib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');
require_once($CFG->dirroot . '/completion/classes/helper.php');

global $DB, $USER;

$ojtid = required_param('ojtid', PARAM_INT);
$userid = required_param('userid', PARAM_INT);
$completionstatus = required_param('completionstatus', PARAM_INT);
$modifiedby = optional_param('modifiedby', 0,PARAM_INT);

switch($completionstatus) {
    case OJT_INCOMPLETE:
        $module_completion_status = OJT_COMPLETION_INCOMPLETE;
        break;
    case OJT_COMPLETE:
        $module_completion_status = OJT_COMPLETION_COMPLETE;
        break;
    case OJT_FAILED:
        $module_completion_status = OJT_COMPLETION_FAILED;
        break;
	case OJT_REASSESSMENT:
		$module_completion_status = OJT_COMPLETION_REASSESSMENT;
		break;
}

$completion = ojt_update_completion($userid, $ojtid, $module_completion_status, $modifiedby);

echo json_encode(
    array(
        'msg' => 'success',
        'status' => true
    )
);
exit();