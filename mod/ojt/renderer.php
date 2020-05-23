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

defined('MOODLE_INTERNAL') || die();

use core\output\flex_icon;

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/mod/ojt/lib.php');
require_once($CFG->dirroot.'/mod/ojt/locallib.php');

class mod_ojt_renderer extends plugin_renderer_base {

    function config_topics($ojt, $config=true) {
        global $DB;

        $out = '';
        $out .= html_writer::start_tag('div', array('id' => 'config-mod-ojt-topics'));
        
        // KINEO CCM
        // get sorted recrod
        //$topics = $DB->get_records('ojt_topic', array('ojtid' => $ojt->id), 'id');
        $topics = ojt_get_topics($ojt->id);
        if (empty($topics)) {
            return html_writer::tag('p', get_string('notopics', 'ojt'));
        }
        // MPIHAS-384
        // KINEO CCM
        // Build sortable topic
        $topics_li = '';
        foreach ($topics as $topic) {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic'));
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-heading'));
            $optionalstr = $topic->completionreq == OJT_OPTIONAL ? ' ('.get_string('optional', 'ojt').')' : '';
            $out .= format_string($topic->name).$optionalstr;
            if ($config) {
                $out .= $this->render_add_topics_dropdown($ojt->id, $topic->id);
                $editurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id));
                $out .= $this->output->action_icon($editurl, new flex_icon('edit', ['alt' => get_string('edittopic', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topic.php', array('bid' => $ojt->id, 'id' => $topic->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl, new flex_icon('delete', ['alt' => get_string('deletetopic', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');

            $out .= $this->config_topic_items($ojt->id, $topic->id, $config);
            $out .= html_writer::end_tag('div');
            
            // MPIHAS-384
            // KINEO CCM
            // Build sortable topic
            $topics_li .= html_writer::tag('li',format_string($topic->name) .
                            html_writer::tag('input', null, 
                                array(
                                    'name' => 'topic_ids[]', 
                                    'value' => $topic->id, 
                                    'type' => 'hidden'
                                )
                            )// hidden input field for ordering on the server site via AJAX
                        );
                            
        }

        $out .= html_writer::end_tag('div');
        
        // MPIHAS-384
        // KINEO CCM
        // Popup to sort topics
        $out .= html_writer::div(null, 'ojt-modal-overlay');
        $out .= html_writer::start_div(null, array('id' => 'ojt-modal')); // start id="ojt-modal"
        $out .= html_writer::div(
                    html_writer::tag('h2', get_string('sorttopic','mod_ojt')), 
                    'ojt-modal-header'
                ); // Modal header
        
        // start modal body
        $out .= html_writer::start_div('ojt-modal-body');
        // sort topics form
        $out .= html_writer::start_tag('form', array('id' => 'ojt-topic-sort-form'));
        // ojt id hidden field
        $out .= html_writer::tag('input', null, 
                                array(
                                    'name' => 'ojtid', 
                                    'value' => $ojt->id, 
                                    'type' => 'hidden'
                                )
                            );
        $out .= html_writer::tag('ul', $topics_li, array('class' => 'ojt-sortable-topics'));
        // close topics sort form
        $out .= html_writer::end_tag('form');
        // close modal body
        $out .= html_writer::end_div(); 
        // create footer
        $out .= html_writer::div(
                    html_writer::tag('button',get_string('btn_sorttopic', 'mod_ojt'), array('id' => 'save-sort-ojt-topics')) .
                    html_writer::tag('button',get_string('btn_cancel', 'mod_ojt'), array('id' => 'cancel-sort-ojt-topics')) 
                ,'ojt-modal-footer');
        // close modal
        $out .= html_writer::end_div();
        // END KINEO CCM
        
        return $out;
    }
    
    /**
     * KINEO CCM 
     * To add a dropdown to add 2 links for menu type questions and default text type question
     * 
     * @param type $ojtid
     * @param type $topicid
     */
    function render_add_topics_dropdown($ojtid, $topicid) {
        $additemtext_url = new moodle_url('/mod/ojt/topicitem.php', array('bid' => $ojtid, 'tid' => $topicid, 'type' => OJT_QUESTION_TYPE_TEXT));
        $additemmenu_url = new moodle_url('/mod/ojt/topicitem.php', array('bid' => $ojtid, 'tid' => $topicid, 'type' => OJT_QUESTION_TYPE_DROPDOWN));
        
        $out = html_writer::start_div('dropdown ojtaddtopic-dropdown');
            $out .= html_writer::tag('button',
                    get_string('additem', 'mod_ojt') . html_writer::span(null, 'caret'),
                    array(
                        'class' => 'btn btn-default dropdown-toggle',
                        'type' => 'button',
                        'data-toggle' => 'dropdown'
                        ));
            $out .= html_writer::start_tag('ul', array('class' => 'dropdown-menu'));
                $out .= html_writer::tag('li', html_writer::link($additemtext_url, get_string('textquestion', 'mod_ojt')));
                $out .= html_writer::tag('li', html_writer::link($additemmenu_url, get_string('menuquestion', 'mod_ojt')));
            $out .= html_writer::end_tag('ul'); // dropdown
        $out .= html_writer::end_div(); // dropdown
        
        return $out;
    }

    function config_topic_items($ojtid, $topicid, $config=true) {
        global $DB;

        $out = '';

        // KINEO CCM
        // get sorted records
        //$items = $DB->get_records('ojt_topic_item', array('topicid' => $topicid), 'id');
        $items = ojt_get_topic_items($topicid);
        
        // MPIHAS-384 - Sort Form
        $out .= html_writer::start_tag('form', array('id' => 'ojt-topic-items-sort-form-'.$topicid));
        $out .= html_writer::tag('input', null, 
                    array(
                        'name' => 'topicid', 
                        'value' => $topicid, 
                        'type' => 'hidden'
                        )
                    );
        
        $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-items'));        
        foreach ($items as $item) {
            $out .= html_writer::start_tag('div', array('class' => 'config-mod-ojt-topic-item'));
            // KINEO CCM
            $out .= html_writer::tag('input', null, 
                    array(
                        'name' => 'topic_items_ids[]', 
                        'value' => $item->id, 
                        'type' => 'hidden'
                        )
                    );
            
            $optionalstr = $item->completionreq == OJT_OPTIONAL ? ' ('.get_string('optional', 'ojt').')' : '';
            $out .= format_string($item->name).$optionalstr;
            if ($config) {
                $editurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id, 'type' => $item->type));
                $out .= $this->output->action_icon($editurl, new flex_icon('edit', ['alt' => get_string('edititem', 'ojt')]));
                $deleteurl = new moodle_url('/mod/ojt/topicitem.php',
                    array('bid' => $ojtid, 'tid' => $topicid, 'id' => $item->id, 'delete' => 1));
                $out .= $this->output->action_icon($deleteurl, new flex_icon('delete', ['alt' => get_string('deleteitem', 'ojt')]));
            }
            $out .= html_writer::end_tag('div');
        }
        $out .= html_writer::end_tag('div');
        
        // KINEO CCM 
        $sort_button = html_writer::tag('button', get_string('btn_sorttopicitems', 'mod_ojt'), 
                array(
                        'type' => 'button', 
                        'class' => 'btn-sort-topic-items',
                        'data-topicid' => $topicid
                    )
                );
        $out .= html_writer::div($sort_button, 'sortbutton-container');
        // close Sort Form ojt-topic-items-sort-form
        $out .= html_writer::end_tag('form');
        
        return $out;
    }

    // Build a user's ojt form
    function user_ojt($userojt, $evaluate=false, $signoff=false, $itemwitness=false) {
        global $CFG, $DB, $USER, $PAGE;

        $out = '';
        $out = html_writer::start_tag('div', array('id' => 'mod-ojt-user-ojt'));

        $course = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        foreach ($userojt->topics as $topic) {
            $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
            switch ($topic->status) {
                case OJT_COMPLETE:
                    $completionicon = 'check-success';
                    break;
                case OJT_REQUIREDCOMPLETE:
                    $completionicon = 'check-warning';
                    break;
                default:
                    $completionicon = 'times-danger';
            }
            if (!empty($completionicon)) {
                $completionicon = $this->output->flex_icon($completionicon, ['alt' => get_string('completionstatus'.$topic->status, 'ojt')]);
            }
            $completionicon = html_writer::tag('span', $completionicon,
                array('class' => 'ojt-topic-status'));
            $optionalstr = $topic->completionreq == OJT_OPTIONAL ?
                html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
            $out .= html_writer::tag('div', format_string($topic->name).$optionalstr.$completionicon,
                array('class' => 'mod-ojt-topic-heading collapsed'));

            $table = new html_table();
            $table->attributes['class'] = 'mod-ojt-topic-items generaltable';
            $table->attributes['style'] = 'display:none;';
            if ($userojt->itemwitness) {
                $table->head = array('', '', get_string('witnessed', 'mod_ojt'));
            }
            $table->data = array();

            foreach ($topic->items as $item) {
                $row = array();
                $optionalstr = $item->completionreq == OJT_OPTIONAL ?
                    html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
                $row[] = format_string($item->name).$optionalstr;
                if ($evaluate) {
                    if($item->type == OJT_QUESTION_TYPE_DROPDOWN) {
                        $cellcontent = $this->render_menu_question_options($item, 'ojt-menu-question-select');
                    } else {
                        $completionicon = $item->status == OJT_COMPLETE ? 'completion-manual-y' : 'completion-manual-n';
                        $cellcontent = html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
                        $cellcontent .= $this->output->flex_icon($completionicon, ['classes' => 'ojt-completion-toggle']);
                        $cellcontent .= html_writer::tag('textarea', $item->comment,
                            array('name' => 'comment-'.$item->id, 'rows' => 8, 'cols' => 80,
                                'class' => 'ojt-completion-comment-no', 'ojt-item-id' => $item->id));
                        $cellcontent .= html_writer::tag('div', format_text($item->comment, FORMAT_PLAIN),
                            array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
                        $cellcontent .= html_writer::end_tag('div');
                    }
                } else {
                    // Show static stuff.
                    $cellcontent = '';
                    if ($item->status == OJT_COMPLETE) {
                        $cellcontent .= $this->output->flex_icon('check-success',
                            ['alt' => get_string('completionstatus'.OJT_COMPLETE, 'ojt')]);
                    } else {
                        $cellcontent .= $this->output->flex_icon('times-danger',
                            ['alt' => get_string('completionstatus'.OJT_INCOMPLETE, 'ojt')]);
                    }

                    $cellcontent .= format_text($item->comment, FORMAT_PLAIN);
                }
                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'modifier');
                $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timemodified, $userobj),
                    array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

                if ($item->allowfileuploads || $item->allowselffileuploads) {
                    $cellcontent .= html_writer::tag('div', $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
                        array('class' => 'mod-ojt-topicitem-files'));

                    if (($evaluate && $item->allowfileuploads) || ($userojt->userid == $USER->id && $item->allowselffileuploads)) {
                        $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php', array('userid' => $userojt->userid, 'tiid' => $item->id));
                        $cellcontent .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'), 'get');
                    }
                }

                $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-completion'));

                if ($userojt->itemwitness) {
                    $cellcontent = '';
                    if ($itemwitness) {
                        $witnessicon = $item->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
                        $cellcontent .= html_writer:: start_tag('span', array('class' => 'ojt-witness-item', 'ojt-item-id' => $item->id));
                        $cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'ojt-witness-toggle']);
                        $cellcontent .= html_writer::end_tag('div');

                    } else {
                        // Show static witness info
                        if (!empty($item->witnessedby)) {
                            $cellcontent .= $this->output->flex_icon('check-success',
                                ['alt' => get_string('witnessed', 'ojt')]);
                        } else {
                            $cellcontent .= $this->output->flex_icon('times-danger',
                                ['alt' => get_string('notwitnessed', 'ojt')]);
                        }
                    }

                    $userobj = new stdClass();
                    $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
                    $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timewitnessed, $userobj),
                        array('class' => 'mod-ojt-witnessedstr', 'ojt-item-id' => $item->id));

                    $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-item-witness'));
                }

                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

            // Topic signoff
            if ($userojt->managersignoff) {
                $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic-signoff', 'ojt-topic-id' => $topic->id));
                $out .= get_string('managersignoff', 'ojt');
                if ($signoff) {
                    $out .= $this->output->flex_icon($topic->signedoff ? 'completion-manual-y' : 'completion-manual-n',
                        ['classes' => 'ojt-topic-signoff-toggle']);
                } else {
                    if ($topic->signedoff) {
                        $out .= $this->output->flex_icon('check-success', ['alt' => get_string('signedoff', 'ojt')]);
                    } else {
                        $out .= $this->output->flex_icon('times-danger', ['alt' => get_string('notsignedoff', 'ojt')]);
                    }
                }
                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $topic, $prefix = 'signoffuser');
                $out .= html_writer::tag('div', ojt_get_modifiedstr($topic->signofftimemodified, $userobj),
                    array('class' => 'mod-ojt-topic-modifiedstr'));
                $out .= html_writer::end_tag('div');
            }

            // Topic comments
            if ($topic->allowcomments) {
                $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
                require_once($CFG->dirroot.'/comment/lib.php');
                comment::init();
                $options = new stdClass();
                $options->area    = 'ojt_topic_item_'.$topic->id;
                $options->context = $context;
                $options->itemid  = $userojt->userid;
                $options->showcount = true;
                $options->component = 'ojt';
                $options->autostart = true;
                $options->notoggle = true;
                $comment = new comment($options);
                $out .= $comment->output(true);
            }


            $out .= html_writer::end_tag('div');  // mod-ojt-topic
        }
        
        $out .= html_writer::end_tag('div');  // mod-ojt-user-ojt

        return $out;
    }

    protected function list_topic_item_files($contextid, $userid, $topicitemid) {
        $out = array();

        $fs = get_file_storage();
        $files = $fs->get_area_files($contextid, 'mod_ojt', 'topicitemfiles'.$topicitemid, $userid, 'itemid, filepath, filename', false);

        foreach ($files as $file) {
            $filename = $file->get_filename();
            $url = moodle_url::make_pluginfile_url($file->get_contextid(), $file->get_component(), $file->get_filearea(), $file->get_itemid(), $file->get_filepath(), $file->get_filename());
            $out[] = html_writer::link($url, $filename);
        }
        $br = html_writer::empty_tag('br');

        return implode($br, $out);
    }
    
    /**
     * Completions status dropdown
     * 
     * @param type $userojt
     * @return type
     */
    function activity_completion_status_dropdown($userojt) {
    	global $USER;

        $ojt_completion_status = array(
            array('key' => OJT_COMPLETE, 'value' => get_string('achieved', 'mod_ojt'), 'text' => get_string('achieved_modaltext', 'mod_ojt')),
            array('key' => OJT_REASSESSMENT, 'value' => get_string('notachieved', 'mod_ojt'), 'text' => get_string('notachieved_modaltext', 'mod_ojt')),
            array('key' => OJT_FAILED, 'value' => get_string('trainingrequired', 'mod_ojt'), 'text' => get_string('trainingrequired_modaltext', 'mod_ojt'))
        );
        
        $data = new stdClass();
        $data->completion_status = $ojt_completion_status;
        $data->userid = $userojt->userid;
        $data->ojtid = $userojt->id;
        $data->modifiedby = $USER->id;
        switch($userojt->status) {
            case OJT_INCOMPLETE:
                $data->completionclass = 'ojt-incomplete';
                $data->current_completion_status = get_string('notassessed', 'mod_ojt');
                break;
            
            case OJT_COMPLETE:
                $data->completionclass = 'ojt-complete';
                $data->current_completion_status = get_string('achieved', 'mod_ojt');
                break;
            
            case OJT_FAILED:
                $data->completionclass = 'ojt-failed';
                $data->current_completion_status = get_string('trainingrequired', 'mod_ojt');
                break;

	        case OJT_REASSESSMENT:
		        $data->completionclass = 'ojt-incomplete';
		        $data->current_completion_status = get_string('notachieved', 'mod_ojt');
		        break;
        }
        
        return $this->render_from_template('mod_ojt/completion_status_dropdown', $data);     
    }
    
    
    /**
     * HWRHAS-162
     * Pretty much a duplicate of user_ojt function
     * This will however only save OJT evaluation data on one go
     * On user_ojt (The default one), each topics item is update/saved on mouse 
     * interaction such as click on the completion checkbox, or on return key hit on
     * the comment section 
     * 
     * 
     * @global type $CFG
     * @global type $DB
     * @global type $USER
     * @global type $PAGE
     * @param type $userojt
     * @param type $evaluate
     * @param type $signoff
     * @param type $itemwitness
     * @return type
     */
    function user_ojt_save_on_submission($userojt, $evaluate=false, $signoff=false, $itemwitness=false) {
        global $CFG, $DB, $USER, $PAGE;
  
        $out = '';
        $out = html_writer::start_tag('div', array('id' => 'mod-ojt-user-ojt'));
        
        $out .= html_writer::start_tag('form', array('id' => 'mod-ojt-user-evaluate-form'));
        
        
        // userid 
        $out .= html_writer::tag('input', null, 
            array(
                'type' => 'hidden',
                'value' => $userojt->userid,
                'name' => 'learnerid'
            )
        );
        // ojtid
        $out .= html_writer::tag('input', null, 
            array(
                'type' => 'hidden',
                'value' => $userojt->id,
                'name' => 'ojtid'
            )
        );
        if($signoff) {
            $out .= html_writer::tag('input', null, 
                array(
                    'type' => 'hidden',
                    'value' => 1,
                    'name' => 'signoffenabled'
                )
            );
        }
        if($itemwitness) {
            $out .= html_writer::tag('input', null, 
                array(
                    'type' => 'hidden',
                    'value' => 1,
                    'name' => 'witnessenabled'
                )
            );
        }
        
        
        $course = $DB->get_record('course', array('id' => $userojt->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('ojt', $userojt->id, $course->id, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        foreach ($userojt->topics as $topic) {
            $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic', 'id' => "ojt-topic-{$topic->id}"));
            switch ($topic->status) {
                case OJT_COMPLETE:
                    $completionicon = 'check-success';
                    break;
                case OJT_REQUIREDCOMPLETE:
                    $completionicon = 'check-warning';
                    break;
                default:
                    $completionicon = 'times-danger';
            }
            if (!empty($completionicon)) {
                $completionicon = $this->output->flex_icon($completionicon, ['alt' => get_string('completionstatus'.$topic->status, 'ojt')]);
            }
            $completionicon = html_writer::tag('span', $completionicon,
                array('class' => 'ojt-topic-status'));
            $optionalstr = $topic->completionreq == OJT_OPTIONAL ?
                html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
            $out .= html_writer::tag('div', format_string($topic->name).$optionalstr.$completionicon,
                array('class' => 'mod-ojt-topic-heading collapsed'));

            $table = new html_table();
            $table->attributes['class'] = 'mod-ojt-topic-items generaltable';
            $table->attributes['style'] = 'display:none;';
            if ($userojt->itemwitness) {
                $table->head = array('', '', get_string('witnessed', 'mod_ojt'));
            }
            $table->data = array();

            foreach ($topic->items as $item) {
                $row = array();
                $optionalstr = $item->completionreq == OJT_OPTIONAL ?
                    html_writer::tag('em', ' ('.get_string('optional', 'ojt').')') : '';
                $row[] = format_string($item->name).$optionalstr;
                if ($evaluate) {
                    //$completionicon = $item->status == OJT_COMPLETE ? 'completion-manual-y' : 'completion-manual-n';
                    $cellcontent = html_writer::start_tag('div', array('class' => 'ojt-eval-actions', 'ojt-item-id' => $item->id));
                    //$cellcontent .= $this->output->flex_icon($completionicon, ['classes' => 'ojt-completion-toggle-no-click']);
                    if($item->type == OJT_QUESTION_TYPE_TEXT) {
                        $completion_param =  array(
                            'type' => 'checkbox',
                            'name' => 'topicitems_status[]',
                            'value' => $item->id
                        );
                        if($item->status == OJT_COMPLETE) {
                            $completion_param['checked'] = 'checked';
                        }
                        $cellcontent .= html_writer::tag('input',null, $completion_param);
                        $cellcontent .= html_writer::tag('textarea', $item->comment,
                            array('name' => "comments[$item->id]", 'rows' => 8, 'cols' => 80,
                                'class' => 'ojt-completion-comment-prevent-save-on-chage', 'ojt-item-id' => $item->id));
                        $cellcontent .= html_writer::tag('div', format_text($item->comment, FORMAT_PLAIN),
                        array('class' => 'ojt-completion-comment-print', 'ojt-item-id' => $item->id));
                    } else {
                        // dorpdown menu type
                        $cellcontent .= $this->render_menu_question_options($item);
                    }
                            
                    $cellcontent .= html_writer::end_tag('div');
                } else {
                    // Show static stuff.
                    $cellcontent = '';
                    if ($item->status == OJT_COMPLETE) {
                        $cellcontent .= $this->output->flex_icon('check-success',
                            ['alt' => get_string('completionstatus'.OJT_COMPLETE, 'ojt')]);
                    } else {
                        $cellcontent .= $this->output->flex_icon('times-danger',
                            ['alt' => get_string('completionstatus'.OJT_INCOMPLETE, 'ojt')]);
                    }

                    $cellcontent .= format_text($item->comment, FORMAT_PLAIN);
                }
                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'modifier');
                $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timemodified, $userobj),
                    array('class' => 'mod-ojt-modifiedstr', 'ojt-item-id' => $item->id));

                if ($item->allowfileuploads || $item->allowselffileuploads) {
                    $cellcontent .= html_writer::tag('div', $this->list_topic_item_files($context->id, $userojt->userid, $item->id),
                        array('class' => 'mod-ojt-topicitem-files'));

                    if (($evaluate && $item->allowfileuploads) || ($userojt->userid == $USER->id && $item->allowselffileuploads)) {
                        $itemfilesurl = new moodle_url('/mod/ojt/uploadfile.php', array('userid' => $userojt->userid, 'tiid' => $item->id));
                        $cellcontent .= $this->output->single_button($itemfilesurl, get_string('updatefiles', 'ojt'), 'get');
                    }
                }

                $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-completion'));

                if ($userojt->itemwitness) {
                    $cellcontent = '';
                    if ($itemwitness) {
                       // $witnessicon = $item->witnessedby ? 'completion-manual-y' : 'completion-manual-n';
                        $cellcontent .= html_writer:: start_tag('span', array('class' => 'ojt-witness-item', 'ojt-item-id' => $item->id));
                        //$cellcontent .= $this->output->flex_icon($witnessicon, ['classes' => 'ojt-witness-toggle-no-click']);
                        $witness_param = array(
                            'type' => 'checkbox',
                            'name' => 'topicitems_witnessed[]',
                            'value' => $item->id
                        );
                        if($item->witnessedby) {
                            $witness_param['checked'] = 'checked';
                        }
                        $cellcontent .= html_writer::tag('input',null,$witness_param);
                        $cellcontent .= html_writer::end_tag('div');

                    } else {
                        // Show static witness info
                        if (!empty($item->witnessedby)) {
                            $cellcontent .= $this->output->flex_icon('check-success',
                                ['alt' => get_string('witnessed', 'ojt')]);
                        } else {
                            $cellcontent .= $this->output->flex_icon('times-danger',
                                ['alt' => get_string('notwitnessed', 'ojt')]);
                        }
                    }

                    $userobj = new stdClass();
                    $userobj = username_load_fields_from_object($userobj, $item, $prefix = 'itemwitness');
                    $cellcontent .= html_writer::tag('div', ojt_get_modifiedstr($item->timewitnessed, $userobj),
                        array('class' => 'mod-ojt-witnessedstr', 'ojt-item-id' => $item->id));

                    $row[] = html_writer::tag('p', $cellcontent, array('class' => 'ojt-item-witness'));
                }

                $table->data[] = $row;
            }

            $out .= html_writer::table($table);

            // Topic signoff
            if ($userojt->managersignoff) {
                $out .= html_writer::start_tag('div', array('class' => 'mod-ojt-topic-signoff', 'ojt-topic-id' => $topic->id));
                $out .= get_string('managersignoff', 'ojt');
                if ($signoff) {
                    //$out .= $this->output->flex_icon($topic->signedoff ? 'completion-manual-y' : 'completion-manual-n',
                    //    ['classes' => 'ojt-topic-signoff-toggle-no-click']);
                    $signoff_param = array(
                        'type' => 'checkbox',
                        'name' => 'topicitems_signoff[]',
                        'value' => $topic->id
                    );
                    if($topic->signedoff) {
                        $signoff_param['checked'] = 'checked';
                    }
                    $out .= html_writer::tag('input',null,$signoff_param);
                } else {
                    if ($topic->signedoff) {
                        $out .= $this->output->flex_icon('check-success', ['alt' => get_string('signedoff', 'ojt')]);
                    } else {
                        $out .= $this->output->flex_icon('times-danger', ['alt' => get_string('notsignedoff', 'ojt')]);
                    }
                }
                $userobj = new stdClass();
                $userobj = username_load_fields_from_object($userobj, $topic, $prefix = 'signoffuser');
                $out .= html_writer::tag('div', ojt_get_modifiedstr($topic->signofftimemodified, $userobj),
                    array('class' => 'mod-ojt-topic-modifiedstr'));
                $out .= html_writer::end_tag('div');
            }

            // Topic comments
            if ($topic->allowcomments) {
                $out .= $this->output->heading(get_string('topiccomments', 'ojt'), 4);
                require_once($CFG->dirroot.'/comment/lib.php');
                comment::init();
                $options = new stdClass();
                $options->area    = 'ojt_topic_item_'.$topic->id;
                $options->context = $context;
                $options->itemid  = $userojt->userid;
                $options->showcount = true;
                $options->component = 'ojt';
                $options->autostart = true;
                $options->notoggle = true;
                $comment = new comment($options);
                $out .= $comment->output(true);
            }


            $out .= html_writer::end_tag('div');  // mod-ojt-topic
        }
        
        $out .= html_writer::tag('hr', null);
        $out .= html_writer::tag('button', get_string('submit', 'mod_ojt'), 
            array(
                'id' => 'mod-ojt-submit-evaluate-btn', 
                'class' => 'btn btn-default',
                'type' => 'button'
            )
        );
        
        // evaluator id for witness etc 
        $out .= html_writer::tag('input', null, array('name' => 'evaluatorid', 'value' => $USER->id, 'type' => 'hidden'));

        $out .= html_writer::end_tag('form'); // mod-ojt-user-evaluate-form
        
        $out .= html_writer::end_tag('div');  // mod-ojt-user-ojt

        return $out;
    }
    
    /**
     * Return select dorpdown for menu type question
     *  
     * @param type $item
     * @return string
     */
    function render_menu_question_options($item, $additional_class = '') {
        $options = $item->other;
        if(empty($options)) {
            return '';
        }
        $options_array = explode("\n", $options);
        $out = html_writer::start_tag('select', array('name' => 'menuoptions['.$item->id.']', 'class' => $additional_class, 'ojt-item-id' => $item->id));
        foreach($options_array as $option) {
            $params = array('value' => $option);
            if(trim($option) == trim($item->comment)) {
                $params['selected'] = true;
            }
            $out .= html_writer::tag('option', $option, $params);
        }
        $out .= html_writer::end_tag('select');
        
        return $out;
    }
}

