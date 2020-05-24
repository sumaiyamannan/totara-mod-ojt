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

global $DB;

$status = optional_param('topicitems_status', null, PARAM_INT);
$witnessed = optional_param('topicitems_witnessed', null, PARAM_INT);
$signoff = optional_param('topicitems_signoff', null, PARAM_INT);
$comments = optional_param('comments', null, PARAM_TEXT);
$signoffenabled = optional_param('signoffenabled', null, PARAM_INT);
$witnessenabled = optional_param('witnessenabled', null, PARAM_INT);
$learnerid = required_param('learnerid', PARAM_INT);
$ojtid = required_param('ojtid', PARAM_INT);
$evaluatorid = required_param('evaluatorid', PARAM_INT);
$menuoptions = optional_param('menuoptions', null, PARAM_TEXT);

// date format
$dateformat = get_string('strftimedatetimeshort', 'core_langconfig');

// update ojt_completion
$ojt_topic_items = ojt_get_topic_items_by_ojtid($ojtid);
if(!empty($ojt_topic_items)) {
    foreach ($ojt_topic_items as $item) {
        $item_status = in_array($item->id, $status) ? OJT_COMPLETE : OJT_INCOMPLETE;
        // HWRHAS-161
        $item_status = OJT_INCOMPLETE;
        if(!empty($menuoptions[$item->id])) {
            $item_status =  OJT_COMPLETE;
        } else {
            $item_status = in_array($item->id, $status) ? OJT_COMPLETE : OJT_INCOMPLETE;
        }
        $completion_record = $DB->get_record('ojt_completion', 
            array(
                'ojtid' => $ojtid,
                'userid' => $learnerid,
                'type' => OJT_CTYPE_TOPICITEM,
                'topicitemid' => $item->id
            )
        );
        if(empty($completion_record)) {
            $completion_record = new stdClass();
            $completion_record->userid = $learnerid;
            $completion_record->type = OJT_CTYPE_TOPICITEM;
            $completion_record->ojtid = $ojtid;
            $completion_record->topicid = $item->topicid;
            $completion_record->topicitemid = $item->id;
        } 
        
        $completion_record->status = $item_status;
        // HWRHAS-161
        if(!empty($menuoptions[$item->id])) {
            $completion_record->comment = $menuoptions[$item->id]; 
        } else {
            $completion_record->comment = !empty($comments[$item->id]) ? 
                $comments[$item->id] . ' - ' . userdate(time(), $dateformat) . '.' : 
                null;
        }
        $completion_record->timemodified = time();
        $completion_record->modifiedby = $evaluatorid;
        
        if(!empty($completion_record->id)) {
            // get record and update
            // highly unlikely if the OJT has been set up properly
            // with saveallonsubmit checked on mod_form.php
            $DB->update_record('ojt_completion', $completion_record);
        } else {
            $DB->insert_record('ojt_completion', $completion_record);
        }
        
        
        if(!empty($witnessenabled) && in_array($item->id, $witnessed)) {
            $item_witnessed = $DB->get_record('ojt_item_witness', array(
                'userid' => $learnerid,
                'topicitemid' => $item->id
            ));
            if(!empty($item_witnessed)) {
                $item_witnessed->witnessedby = $evaluatorid;
                $item_witnessed->timewitnessed = time();
                
                $DB->update_record('ojt_item_witness', $item_witnessed);
            } else {
                $item_witnessed = new stdClass();
                $item_witnessed->userid = $learnerid;
                $item_witnessed->topicitemid = $item->id;
                $item_witnessed->witnessedby = $evaluatorid;
                $item_witnessed->timewitnessed = time();
                
                $DB->insert_record('ojt_item_witness', $item_witnessed);
            }
        }
    }
}
// update ojt_topic_signoff where applicable
if(!empty($signoffenabled)) {
    foreach ($signoff as $sf) {
        $signedoff = $DB->get_record('ojt_topic_signoff', array(
            'userid' => $learnerid,
            'topicid' => $sf
        ));
        
        // if records exists
        // continue
        if(!empty($signedoff)) {
            continue;
        }
        // else add as new record
        $signedoff = new stdClass();
        $signedoff->userid = $learnerid;
        $signedoff->topicid = $sf;
        $signedoff->signedoff = 1;
        $signedoff->comment = null;
        $signedoff->timemodified = time();
        $signedoff->modifiedby = $evaluatorid;
        
        $DB->insert_record('ojt_topic_signoff', $signedoff);
    }
}

// update ojt topic completions
$ojt_topics = ojt_get_topics($ojtid);
if(!empty($ojt_topics)) {
    foreach ($ojt_topics as $topic) {
        ojt_update_topic_completion($learnerid, $ojtid, $topic->id);
    }
}

echo json_encode(
    array(
        'msg' => 'success',
        'status' => true
    )
);
exit();