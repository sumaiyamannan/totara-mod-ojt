<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2017 onwards Totara Learning Solutions LTD
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
 * @author Brian Barnes <brian.barnes@totaralearning.com>
 * @package core_completion
 */

namespace core_completion\form_controllers;

use totara_form\form_controller;
use core_completion\forms\activity_completion;

/**
 * Base class for form controllers.
 *
 * @package   core_completion
 * @copyright 2017 Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Brian Barnes <brian.barnes@totaralearning.com>
 */
class activity_completion_controller extends form_controller {


    /** @var activity_completion $form */
    protected $form;

    /**
     * This method is responsible for:
     *  - access control
     *  - getting of current data
     *  - getting of parameters
     *
     * and returning of the form instance.
     *
     * @param string $idsuffix string extra for identifier to allow repeated forms on one page
     * @return activity_completion
     */
    public function get_ajax_form_instance($idsuffix) {
        $activity_id = required_param('activity_id', PARAM_INT);
        $mod_info = get_course_and_cm_from_cmid($activity_id);
        $compinfo = new \completion_info($mod_info[0]);
        $completiondata = $compinfo->get_data($mod_info[1]);

        // Access control first.
        require_login($mod_info[0]);
        require_sesskey();

        // Create the form instance.
        $this->form = new activity_completion(array('activity_id' => $activity_id, 'completed' => $completiondata->completionstate), null, $idsuffix);

        return $this->form;
    }

    /**
     * Process the submitted form.
     *
     * @return array processed data
     */
    public function process_ajax_data() {
        global $USER, $DB;
        $result = array();
        $result['data'] = (array)$this->form->get_data();

        //do something with $USER->id & $result['data']['activityid']
        $cm = get_coursemodule_from_id(null, $result['data']['activity_id'], null, true, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

        $completion = new \completion_info($course);
        $completion->update_state($cm, $result['data']['completed'], $USER->id);

        return $result;
    }
}
