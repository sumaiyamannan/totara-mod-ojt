<?php
/*
 * Copyright (C) 2015 onwards Catalyst IT
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Eugene Venter <eugene@catalyst.net.nz>
 * @package mod_ojt
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');
require_once(dirname(__FILE__).'/forms.php');

$ojtid = required_param('bid', PARAM_INT); // OJT instance id.
$topicid  = required_param('tid', PARAM_INT);  // Topic id.
$itemid = optional_param('id', 0, PARAM_INT);  // Topic item id.
$delete = optional_param('delete', 0, PARAM_BOOL);
$itemtype = optional_param('type', OJT_ITEM_TYPE_TEXT, PARAM_INT); // Topic item type (text / select).
$action  = optional_param('action', '', PARAM_ALPHANUMEXT);  // Action
$topicitemid  = optional_param('n', 0, PARAM_INT);  // Topic item id

$ojt = $DB->get_record('ojt', array('id' => $ojtid), '*', MUST_EXIST);
$course = $DB->get_record('course', array('id' => $ojt->course), '*', MUST_EXIST);
$cm = get_coursemodule_from_instance('ojt', $ojt->id, $course->id, false, MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/ojt:manage', $context);

$PAGE->set_url('/mod/ojt/topicitem.php', array('bid' => $ojtid, 'tid' => $topicid, 'id' => $itemid));

// Handle actions
if ($delete) {
    $confirm = optional_param('confirm', 0, PARAM_BOOL);
    if (!$confirm) {
        echo $OUTPUT->header();
        $confirmurl = $PAGE->url;
        $confirmurl->params(array('delete' => 1, 'confirm' => 1, 'sesskey' => sesskey()));
        echo $OUTPUT->confirm(get_string('confirmitemdelete', 'ojt'), $confirmurl, $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }

    require_sesskey();

    ojt_delete_topic_item($itemid, $context);
    $redirecturl = new moodle_url('/mod/ojt/manage.php', array('cmid' => $cm->id));
    totara_set_notification(get_string('itemdeleted', 'ojt'), $redirecturl, array('class' => 'notifysuccess'));
}

// WR#345138: Add in menu select topic items.
switch ($itemtype) {
    case OJT_ITEM_TYPE_SELECT:
        $form = new ojt_topic_item_select_form(null, array('ojtid' => $ojtid, 'topicid' => $topicid, 'type' => $itemtype));
        break;
    case OJT_ITEM_TYPE_TEXT:
    default:
    $form = new ojt_topic_item_text_form(null, array('ojtid' => $ojtid, 'topicid' => $topicid, 'type' => $itemtype));
}

if ($data = $form->get_data()) {
    // Save topic
    $item = new stdClass();
    $item->topicid = $data->tid;
    $item->name = $data->name;
    $item->completionreq = $data->completionreq;
    $item->allowfileuploads = $data->allowfileuploads;
    $item->allowselffileuploads = $data->allowselffileuploads;
    $item->type = $data->type;
    if(!empty($data->selectionoptions)) {
        $item->other = $data->selectionoptions;
    }

    if (empty($data->id)) {
        // Add
        $topicitemcount = $DB->get_record('ojt_topic_item', array('topicid' => $topicid), 'count(*)', MUST_EXIST);
        $item->position = $topicitemcount->count + 1;
        $DB->insert_record('ojt_topic_item', $item);
    } else {
        // Update
        $item->id = $data->id;
        $DB->update_record('ojt_topic_item', $item);
    }

    redirect(new moodle_url('/mod/ojt/manage.php', array('cmid' => $cm->id)));
}

switch ($action) {

    case 'topicitemdown':
        $topicitem = $DB->get_record('ojt_topic_item', array('id' => $topicitemid), '*', MUST_EXIST);
        $itemrs = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'position');

        $itemrs = array_values($itemrs);

        if(array_move($itemrs, $topicitem->position, $topicitem->position + 1) !== false) {
            foreach ($itemrs as $index => $itemr) {
                $itemr->position = $index;
                $DB->update_record('ojt_topic_item', $itemr);
            }
            totara_set_notification(get_string('topicreordered', 'ojt'), null, array('class' => 'notifysuccess'));
        }

        redirect(new moodle_url('/mod/ojt/manage.php', array('cmid' => $cm->id)));
        break;

    case 'topicitemup':
        $topicitem = $DB->get_record('ojt_topic_item', array('id' => $topicitemid), '*', MUST_EXIST);
        $itemrs = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'position');

        $itemrs = array_values($itemrs);

        if(array_move($itemrs, $topicitem->position, $topicitem->position - 1) !== false) {
            foreach ($itemrs as $index => $itemr) {
                $itemr->position = $index;
                $DB->update_record('ojt_topic_item', $itemr);
            }
            totara_set_notification(get_string('topicreordered', 'ojt'), null, array('class' => 'notifysuccess'));
        }

        redirect(new moodle_url('/mod/ojt/manage.php', array('cmid' => $cm->id)));
        break;

    default:
        break;
}

// Print the page header.
$actionstr = empty($itemid) ? get_string('additem', 'ojt') : get_string('edititem', 'ojt');
$PAGE->set_title(format_string($ojt->name));
$PAGE->set_heading(format_string($ojt->name).' - '.$actionstr);

// Output starts here.
echo $OUTPUT->header();

// Replace the following lines with you own code.
echo $OUTPUT->heading($PAGE->heading);

if (!empty($itemid)) {
    $item = $DB->get_record('ojt_topic_item', array('id' => $itemid), '*', MUST_EXIST);
    // Sense check against legacy values.
    $item->selectionoptions = !empty($item->other) ? $item->other : '';
    $form->set_data($item);
}

// Display
$form->display();

// Finish the page.
echo $OUTPUT->footer();
