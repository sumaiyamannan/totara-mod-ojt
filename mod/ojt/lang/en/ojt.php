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


/**
 * English strings for ojt
 */

defined('MOODLE_INTERNAL') || die();

$string['accessdenied'] = 'Access denied';
$string['additem'] = 'Add topic item';
$string['addtopic'] = 'Add topic';
$string['allowcomments'] = 'Allow comments';
$string['allowfileuploads'] = 'Allow \'evaluator\' file uploads';
$string['allowselffileuploads'] = 'Allow \'owner\' file uploads';
$string['edititem'] = 'Edit topic item';
$string['evaluate'] = 'Evaluate';
$string['ojt:addinstance'] = 'Add instance';
$string['ojt:evaluate'] = 'Evaluate';
$string['ojt:evaluateself'] = 'Evaluate self';
$string['ojt:manage'] = 'Manage';
$string['ojt:view'] = 'View';
$string['ojt:signoff'] = 'Sign-off';
$string['ojt:witnessitem'] = 'Witness topic item completion';
$string['ojtfieldset'] = 'Custom example fieldset';
$string['ojtname'] = 'OJT name';
$string['ojtname_help'] = 'The title of your OJT activity.';
$string['ojt'] = 'OJT';
$string['ojtxforx'] = '{$a->ojt} - {$a->user}';
$string['competencies'] = 'Competencies';
$string['competencies_help'] = 'Here you can select which of the assigned course competencies should be marked as proficient upon completion of this topic.

Multiple competencies can be selected by holding down \<CTRL\> and and selecting the items.';

$string['completionstatus'] = 'Completion status';
$string['completionstatus0'] = 'Incomplete';
$string['completionstatus1'] = 'Required complete';
$string['completionstatus2'] = 'Complete';
// HWRHAS-1599
$string['completionstatus3'] = 'Training required';
// HWRHAS-254
$string['completionstatus4'] = 'Re-assessment required';


$string['completiontopics'] = 'All required topics are complete and, if enabled, witnessed.';
$string['confirmtopicdelete'] = 'Are you sure you want to delete this topic?';
$string['confirmitemdelete'] = 'Are you sure you want to delete this topic item?';
$string['deleteitem'] = 'Delete topic item';
$string['deletetopic'] = 'Delete topic';
$string['edittopic'] = 'Edit topic';
$string['edittopics'] = 'Edit topics';
$string['error:ojtnotfound'] = 'OJT not found';
$string['evaluatestudents'] = 'Evaluate students';
$string['filesupdated']  = 'Files updated';
$string['itemdeleted'] = 'Topic item deleted';
$string['itemwitness'] = 'Item completion witness';
$string['manage'] = 'Manage';
$string['managersignoff'] = 'Manager sign-off';
$string['managertasktcompletionsubject'] = '{$a->user}  is awaiting your sign off for completion of topic {$a->topic} in {$a->courseshortname}';
$string['managertasktcompletionmsg'] = '{$a->user} has completed topic <a href="{$a->topicurl}">{$a->topic}</a>. This topic is now awaiting your sign-off.';
$string['modulename'] = 'OJT';
$string['modulenameplural'] = 'OJTs';
$string['modulename_help'] = 'The OJT module allows for student evaluation based on pre-configured OJT topics and items.';
$string['name'] = 'Name';
$string['notsignedoff'] = 'Not signed off';
$string['notopics'] = 'No topics';
$string['notwitnessed'] = 'Not witnessed';
$string['nousers'] = 'No users...';
$string['optional'] = 'Optional';
$string['optionalcompletion'] = 'Optional completion';
$string['pluginadministration'] = 'OJT administration';
$string['pluginname'] = 'OJT';
$string['printthisojt'] = 'Print this OJT';
$string['report'] = 'Report';
$string['signedoff'] = 'Signed off';
$string['topicdeleted'] = 'Topic deleted';
$string['topiccomments'] = 'Comments';
$string['topicitemfiles'] = 'Files';
$string['topicitemdeleted'] = 'Topic item deleted';
$string['type0'] = 'OJT';
$string['type1'] = 'Topic';
$string['type2'] = 'Item';
$string['updatefiles'] = 'Update files';
$string['witnessed'] = 'Witnessed';
// MPIHAS-384
$string['btn_sorttopicitems'] = 'Save Topic Item Order';
$string['positionupdate_successful'] = 'Position update successfully';
$string['btn_sorttopic'] = 'Save Topic Order';
$string['sorttopic'] = 'Sort Topic';
$string['btn_cancel'] = 'Cancel';
$string['btn_updatetopic_order'] = 'Update topic order';
// MPIHAS-523
$string['allowselfevaluation'] = 'Allow learner self-evaluation';
// HWRHAS-159
$string['completion_status'] = 'Completion status';
$string['achieved'] = 'Assessment complete - competent';
$string['notachieved'] = 'Reassessment required';
$string['trainingrequired'] = 'Stand down recommended';
$string['achieved_modaltext'] = 'You are about to mark this activity\'s status as <strong>Assessment complete - competent</strong>. This means they will continue on the normal reassessment schedule. Please confirm?';
$string['notachieved_modaltext'] = 'You are about to mark this activity\'s status as <strong>Reassessment required</strong>. This means they will need to be seen by a Driver Trainer sooner than the normal reassessment schedule. Please confirm?';
$string['trainingrequired_modaltext'] = 'You are about to mark this activity\'s status as <strong>Stand down recommended</strong>. This means you are recommending to their manager that they are stood down from further driver duties until additional training and a reassessment is complete. Please confirm?.';
$string['cancel'] = 'Cancel';
$string['confirm'] = 'Confirm';
$string['confirm_modal_title'] = 'Confirm completions status';
$string['confirm_modal_body'] = '<span id="ojt-modal-completion-status"></span>';
$string['current_completion_status'] = 'Current completion status';
$string['topic_item'] = 'Topic item';
$string['ojtarchivedfor'] = 'Archived OJT record for {$a}';
$string['ojtarchivedevidence'] = 'Archived OJT Evidence';
$string['archiveojt'] = 'Archive OJT';
// HWRHAS-162
$string['saveallonsubmit'] = 'Save evaluation data on form submit';
$string['saveallonsubmit_help'] = 'When checked, while evaluating the learner, all form data will be saved on form submit instead of individually.';
$string['submit'] = 'Submit';
// HWRHAS-161
$string['menuoptions'] = 'Menu options';
$string['menuoptions_help'] = 'Enter menu options in a new line.';
$string['textquestion'] = 'Text question';
$string['menuquestion'] = 'Menu question';
// HWRHAS-239
$string['questiontype'] = 'Question type';
// HWRHAS-245
$string['notassessed'] = 'Not yet assessed';