<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010 onwards Totara Learning Solutions LTD
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
 * @author David Curry <david.curry@totaralms.com>
 * @package totara
 * @subpackage totara_hierarchy
 */

require_once(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . '/config.php');
require_once($CFG->dirroot . '/totara/hierarchy/prefix/goal/lib.php');

// Check if Goals are enabled.
goal::check_feature_enabled();

$goalpersonalid = required_param('id', PARAM_INT);

require_login();

// Set up the variables for a personal goal.
$goalpersonal = goal::get_goal_item(array('id' => $goalpersonalid), goal::SCOPE_PERSONAL);
$userid = $goalpersonal->userid;
$context = context_user::instance($userid);
$PAGE->set_context($context);

$strmygoals = get_string('mygoals', 'totara_hierarchy');
$mygoalsurl = new moodle_url('/totara/hierarchy/prefix/goal/mygoals.php', array('userid' => $userid));

$goal = new goal();
if (!$permissions = $goal->get_permissions(null, $userid)) {
    // Error setting up page permissions.
    print_error('error:viewusergoals', 'totara_hierarchy');
}

extract($permissions);

$edit_params = array('id' => $goalpersonalid, 'userid' => $userid);
$edit_url = new moodle_url('/totara/hierarchy/prefix/goal/item/edit_personal.php', $edit_params);

$name = format_string($goalpersonal->name);
$scale = $DB->get_record('goal_scale', array('id' => $goalpersonal->scaleid));

// Set up the scale value selector.
if (!empty($goalpersonal->scaleid)) {
    if ($can_edit[$goalpersonal->assigntype]) {
        $scalevalues = $DB->get_records('goal_scale_values', array('scaleid' => $goalpersonal->scaleid));
        $options = array();
        foreach ($scalevalues as $scalevalue) {
            $options[$scalevalue->id] = format_string($scalevalue->name);
        }

        $js_args = array(
            'userid' => $userid,
            'personalscope' => goal::SCOPE_PERSONAL
        );
        $PAGE->requires->js_call_amd('totara_hierarchy/mygoals', 'init_single_personal', $js_args);

        $attributes = array(
            'class' => 'personal_scalevalue_selector',
            'data-goalid' => $goalpersonalid
        );

        $scalevalue = html_writer::select($options, 'personal_scalevalue', $goalpersonal->scalevalueid, null, $attributes);
    } else {
        $scalevalue = $DB->get_field('goal_scale_values', 'name', array('id' => $goalpersonal->scalevalueid));
    }
} else {
    $scalevalue = '';
}

// Set up the page.
$PAGE->navbar->add($strmygoals, $mygoalsurl);
$PAGE->navbar->add($name);
$PAGE->set_url(new moodle_url('/totara/hierarchy/prefix/goal/item/view.php'), array('id' => $goalpersonalid));
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_totara_menu_selected('mygoals');
$PAGE->set_title($strmygoals);
$PAGE->set_heading($strmygoals);

// Setup lightbox.
local_js(array(
    TOTARA_JS_DIALOG,
    TOTARA_JS_TREEVIEW
));

// Check $USER has permission to see this page.
if ($USER->id == $userid) {
    // Either a user viewing their own goals.
    require_capability("totara/hierarchy:viewownpersonalgoal", $context);
} else {
    // Or a manager/admin viewing a staff memebers goals.
    require_capability("totara/hierarchy:viewstaffpersonalgoal", $context);
}

// Start the page.
echo $OUTPUT->header();

// Set up the form here.
echo html_writer::start_tag('div', array('class' => "view_personal_goal"));

// Create edit button.
if ($can_edit[$goalpersonal->assigntype]) {
    $edit_str = get_string('edit');
    $edit_button = ' ' . $OUTPUT->action_icon($edit_url, new pix_icon('t/edit', $edit_str));
} else {
    $edit_button = '';
}

// Set up the heading.
echo $OUTPUT->heading(get_string("personalgoal", 'totara_hierarchy') . $edit_button);

$tabledata = array ();

// Name.
$title = get_string('goaltable:name', 'totara_hierarchy');
$tabledata[$title] = format_string($name);

// Scale name.
$title = get_string('goaltable:scale', 'totara_hierarchy');
$scalename = !empty($scale) ? $scale->name : '';
$tabledata[$title] = format_string($scalename);

// Scale value.
$title = get_string('goaltable:scalevalue', 'totara_hierarchy');
$tabledata[$title] = $scalevalue;

// Target.
$title = get_string('goaltargetdate', 'totara_hierarchy');
if (!empty($goalpersonal->targetdate)) {
    $targetdate = userdate($goalpersonal->targetdate, get_string('datepickerlongyearphpuserdate', 'totara_core'), 99, false);
} else {
    $targetdate = '';
}
$tabledata[$title] = format_string($targetdate);

// Description.
$title = get_string('description', 'totara_hierarchy');
$tabledata[$title] = format_text($goalpersonal->description, FORMAT_HTML, $TEXTAREA_OPTIONS);

echo html_writer::start_tag('dl', array('class' => 'dl-horizontal'));

foreach ($tabledata as $title => $data) {
    echo html_writer::tag('dt', format_string($title));
    echo html_writer::tag('dd', $data);
}

echo html_writer::end_tag('dl');

// End of goal.
echo html_writer::end_tag('div');

// End of page.
echo $OUTPUT->footer();