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

namespace mod_ojt;

//require_once('../../../config.php');
require_once($CFG->dirroot . '/mod/ojt/lib.php');

/**
 * OJT archived types
 */
define('OJT_ARCHIVED', 1);
define('OJT_NOT_ARCHIVED', 0);


/**
 * Mark completed ojt as archived
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojtid
 */
function ojt_markas_archived($ojtid, $userid) {
    global $DB;
    
    $ojt = $DB->get_record('ojt_completion', array(
        'type' => OJT_CTYPE_OJT,
        'ojtid' => $ojtid,
        'userid' => $userid
    ));
    
    if(!empty($ojt)) {
        $ojt->archived = OJT_ARCHIVED;
        $DB->update_record('ojt_completion', $ojt);
    }
}


/**
 * Get completed ojt
 * Modified to get failed ojt as well
 * so any status greater than 2
 * 
 * @global type $DB
 * @return type
 */
function ojt_get_completed_ojts() {
    global $DB, $CFG;
    require_once($CFG->dirroot . '/lib/completionlib.php');
    
    $sql = "SELECT oc.*
              FROM {ojt} ojt 
              JOIN {ojt_completion} oc 
                ON ojt.id = oc.ojtid 
              JOIN {course_modules} cm
                ON cm.instance = ojt.id
              JOIN {course_modules_completion} cmc
                ON (cmc.coursemoduleid = cm.id AND cmc.userid = oc.userid)
             WHERE oc.type = :type
               AND oc.archived = :notarchived
               AND cmc.completionstate > :status
               AND cmc.timecompleted IS NOT NULL
                ";

    $params = array(
        'type' => OJT_CTYPE_OJT,
        'status' => COMPLETION_INCOMPLETE,
        'notarchived' => OJT_NOT_ARCHIVED
    );
    $completed_ojt = $DB->get_records_sql($sql,$params);
    
    return $completed_ojt;
}

/**
 * Puts all the topics into HTML Table for converting to PDF
 * 
 * @param type $topics
 * @return type
 */
function ojt_prepare_topics_html_for_archiving($topics) {
    $out = '';
    
    foreach ($topics as $topic) {
        $out .= \html_writer::tag('h3', $topic['topic']);
        if(!empty($topic['items'])) {
            $table = new \html_table();
            $table->attributes = array('style' => 'text-align: left;', 'cellpadding' => 4); 
            
            $table->head = array(
                get_string('topic_item', 'mod_ojt'),
                get_string('topiccomments', 'mod_ojt')
            );
            
            $count = 0;
            foreach($topic['items'] as $item) {
                $data = array();
                
                $data[] = $item['topicitem'];
                $data[] = $item['comment'];
                $table->data[] = $data;
                if($count % 2) {
                    $table->rowclasses[] = 'even';
                } else {
                    $table->rowclasses[] = 'odd';
                }
                $count++;
            }
            $out .= \html_writer::table($table);
        }
    }
    return $out;
}

/**
 * Get ojt user topics data
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojtid
 * @param type $userid
 * @return type
 */
function ojt_get_user_topics_data($ojtid, $userid) {
    global $DB;
    
    $sql = "SELECT oti.id,
                    ot.name AS topicname, 
                    ot.id AS topicid,
                    oti.name AS topic_itemname, 
                    oc.comment
              FROM {ojt_topic} ot
         LEFT JOIN {ojt_topic_item}  oti
                ON ot.id = oti.topicid
         LEFT JOIN {ojt_completion} oc
                ON oc.topicitemid = oti.id
             WHERE oc.userid = :userid
               AND oc.ojtid = :ojtid
            ";
    
    $records = $DB->get_records_sql($sql, array(
        'userid' => $userid,
        'ojtid' => $ojtid
    ));
    $topics = array();
    if(!empty($records)) {
        foreach ($records as $record) {
            $topics[$record->topicid]['topic'] = $record->topicname;
            $topics[$record->topicid]['items'][] = array(
                'topicitem' => $record->topic_itemname,
                'comment' => $record->comment
            );
        }
    }
    return $topics;
}

/**
 * Returns cmid (id from course module table)
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojtid
 */
function ojt_get_cmid($ojtid) {
    global $DB;
    
    $sql = "SELECT cm.id AS cmid
              FROM {course_modules} cm
              JOIN {modules} m
                ON (cm.module = m.id AND m.name = :module)
              JOIN {ojt} o
                ON o.id = cm.instance
             WHERE o.id = :ojtid";
    
    $cm = $DB->get_record_sql($sql, array('module' => 'ojt', 'ojtid' => $ojtid));
    return !empty($cm) ? $cm->cmid : null;
}

/**
 * Generate pdf and add file to evidence as other
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojtid
 * @param type $userid
 */
function ojt_archive_and_add_pdf_file_to_evidence($ojtid, $userid) {
    global $DB;
    
    raise_memory_limit(MEMORY_EXTRA);
    
    // get topics
    $topics = ojt_get_user_topics_data($ojtid, $userid);
    
    // get html for pdf
    $html = ojt_prepare_topics_html_for_archiving($topics);
    // get user
    $user = $DB->get_record('user', array('id' => $userid));
    $userfullname = fullname($user);
    // get ojt
    $ojt = $DB->get_record('ojt', array('id' => $ojtid));
    
    $title = $ojt->name;
    $subject = get_string('ojtarchivedfor', 'mod_ojt', fullname($user));
    $filename = clean_filename(fullname($user) . '_' . $ojt->name . '_' . time() .'.pdf');   
    $filename = str_replace(' ', '_', $filename);
    
    // insert record into ojt_archives
    $archive = new \stdClass();
    $archive->ojtid = $ojtid;
    $archive->userid = $userid;
    $archive->filename = $filename;
    $archive->timecreated = time();
    
    // update with insert id
    $archive->id = $DB->insert_record('ojt_archives', $archive);
    
    // create new evidence record
    $evidencefileattachment_id = ojt_add_evidence($ojt, $user);    
    
    $context = \context_system::instance();
    
    // get file storage
    $fs = get_file_storage();
    
    // generate pdf content
    $pdfcontent = ojt_generate_pdf($html, $filename, $title, $subject, $userfullname);
    
    // add file to other evidence
    $fileobj = new \stdClass();
    $fileobj->contextid = $context->id;
    $fileobj->component = 'totara_customfield';
    $fileobj->filearea = 'evidence_filemgr';
    $fileobj->itemid = $evidencefileattachment_id;
    $fileobj->filepath = '/';
    $fileobj->filename = $filename;
    $fileobj->userid = $userid;

    // save the file to the db
    $fileinstance = $fs->create_file_from_string($fileobj, $pdfcontent);

    // update the review to show the file id
    $archive->fileid = $fileinstance->get_id();
    // update archive with file id
    $DB->update_record('ojt_archives', $archive); 
    // mark ojt as archived
    ojt_markas_archived($ojtid, $userid);
}


/**
 * Create evidence record for the user
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojt
 * @param type $user
 * @return type
 */
function ojt_add_evidence($ojt, $user) {
    global $DB;
    
    // add to evidence
    $evidence = new \stdClass();
    $evidence->name = $ojt->name;
    $evidence->timecreated = time();
    $evidence->timemodifield = time();
    $evidence->usermodified = null;
    $evidence->evidencetypeid = 0;
    $evidence->userid = $user->id;
    $evidence->readonly = 0;
    
    // update with insert id
    $evidenceid = $DB->insert_record('dp_plan_evidence', $evidence);
    
    // get evidence custom fields
    $evidence_fields = $DB->get_records('dp_plan_evidence_info_field');
    $evidencefileattachment_id = 0;
    
    if(!empty($evidence_fields)) {
        foreach ($evidence_fields as $key => $field) {
            $insertid = null;
            
            // create evidence info data object
            $evidence_info_data = new \stdClass();
            $evidence_info_data->fieldid = $key;
            $evidence_info_data->evidenceid = $evidenceid;
            
            switch ($field->shortname) {
                case 'evidencedescription':
                    $evidence_info_data->data = get_string('ojtarchivedevidence', 'mod_ojt');
                    break;
                case 'evidencefileattachments':
                    $evidence_info_data->data = null;
                    // insert records
                    $evidencefileattachment_id = $evidence_info_data->id =  $DB->insert_record('dp_plan_evidence_info_data', $evidence_info_data);
                    // update data with the same value as id
                    $evidence_info_data->data = $evidencefileattachment_id;
                    $DB->update_record('dp_plan_evidence_info_data', $evidence_info_data);
                    break;
                case 'evidencedatecompleted':
                    $evidence_info_data->data = ojt_completed_time($ojt->id, $user->id);
                    break;
                default:
                    $evidence_info_data->data = null;
                    break;
            }
            // insert records
            if(empty($evidence_info_data->id)) {
                $DB->insert_record('dp_plan_evidence_info_data', $evidence_info_data);
            }
        }
    }
    // return dp_plan_evidence_info_data id for custom field evidencefileattachments
    // this will be used in the files table as itemid
    return $evidencefileattachment_id;
}

/**
 * Get ojt completion time
 * 
 * @global \mod_ojt\type $DB
 * @param type $ojtid
 * @param type $userid
 * @return type
 */
function ojt_completed_time($ojtid, $userid) {
    global $DB;
    
    $cmid = ojt_get_cmid($ojtid);
    
    $module_completion = $DB->get_record('course_modules_completion', array('coursemoduleid' => $cmid, 'userid' => $userid));
    if(!empty($module_completion)) {
        return $module_completion->timecompleted;
    }
    return null;
}

/**
 * Generate pdf from HTML
 * 
 * @global type $CFG
 * @global \mod_ojt\type $DB
 * @param type $html
 * @param type $filename
 * @param type $title
 * @param type $subject
 * @return type
 */
function ojt_generate_pdf($html, $filename, $title, $subject, $userfullname) {
    global $CFG, $DB;
    ob_start();
   
    raise_memory_limit(MEMORY_EXTRA);
    
    \core_php_time_limit::raise(300);
    
    require_once($CFG->dirroot.'/mod/ojt/tcpdf/tcpdf.php');  
    
    // create new PDF document
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // set document information
    $pdf->SetCreator('');
    $pdf->SetAuthor('');
    $pdf->SetTitle($title);
    $pdf->SetSubject($subject);
    
    // set default header data
    $pdf->SetHeaderData(null, null, $title, $userfullname, array(0,64,255), array(0,64,128));
    $pdf->setFooterData(array(0,64,0), array(0,64,128));

    // set margins
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // set auto page breaks
    $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

    
    // Set font
    // dejavusans is a UTF-8 Unicode font, if you only need to
    // print standard ASCII chars, you can use core fonts like
    // helvetica or times to reduce file size.
    $pdf->SetFont('dejavusans', '', 14, '', true);
    
    // Add a page
    // This method has several options, check the source code documentation for more information.
    $pdf->AddPage();
    
    // some css
    $css = <<<EOF
        <style>
            table th {
                font-weight: bold;
                border-bottom: 1px solid #333333;
            }
            table td {
                background-color: #cccccc;
            }
            table tr.even td {
                background-color: #eaeaea;
            }
        </style>
EOF;
    
    $pdf->writeHTML($css . $html, true, false, true, false, '');
    
    return $pdf->Output($filename,  'S');
}