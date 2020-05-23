<?php

/**
 * AJAX script to update topic items position
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

$topicid = required_param('topicid', PARAM_INT);
$topic_items_ids = required_param('topic_items_ids', PARAM_INT);

// get current topic items
$topic_items = $DB->get_records('ojt_topic_item', array('topicid' => $topicid));

foreach($topic_items_ids as $key => $itemid) {
    if(!empty($topic_items[$itemid])) {
        $topic_items[$itemid]->position = $key;
        
        // update items
        $DB->update_record('ojt_topic_item', $topic_items[$itemid]);
    }
}

$msg = array(
    'success' => true,
    'msg' => get_string('positionupdate_successful', 'mod_ojt')
);

echo json_encode($msg); 
exit();