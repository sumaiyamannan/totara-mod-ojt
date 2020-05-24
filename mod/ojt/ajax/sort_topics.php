<?php

/**
 * AJAX script to update topic position
 *
 * @package    mod
 * @subpackage ojt
 * @copyright  &copy; 2018 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

define('NO_MOODLE_COOKIES', true);
define('AJAX_SCRIPT', true);

require_once('../../../config.php');

global $DB, $PAGE;

$ojtid = required_param('ojtid', PARAM_INT);
$topic_ids = required_param('topic_ids', PARAM_INT);

// get current topic for OJT
$topics = $DB->get_records('ojt_topic', array('ojtid' => $ojtid));

foreach($topic_ids as $key => $topic_id) {
    if(!empty($topics[$topic_id])) {
        $topics[$topic_id]->position = $key;
        
        // update items
        $DB->update_record('ojt_topic',  $topics[$topic_id]);
    }
}

$msg = array(
    'success' => true,
    'msg' => get_string('positionupdate_successful', 'mod_ojt')
);

echo json_encode($msg); 
exit();