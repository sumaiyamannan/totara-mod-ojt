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

namespace mod_ojt\rb\content;

use reportbuilder;
use totara_reportbuilder\rb\content\base;

/**
 * Restrict content by ojt completion type
 *
 * Pass in an integer that represents a ojt completion type, e.g OJT_CTYPE_TOPIC
 */
final class ojt_completion_type extends base {

    const TYPE = 'ojt_completion_type_content';

    /**
     * Generate the SQL to apply this content restriction
     *
     * @param string $field SQL field to apply the restriction against
     * @param integer $reportid ID of the report
     *
     * @return array containing SQL snippet to be used in a WHERE clause, as well as array of SQL params
     */
    public function sql_restriction($field, $reportid) {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/mod/ojt/lib.php');

        $settings = reportbuilder::get_all_settings($reportid, self::TYPE);

        return array('base.type = :crbct', array('crbct' => $settings['completiontype']));
    }

    /**
     * Generate a human-readable text string describing the restriction
     *
     * @param string $title Name of the field being restricted
     * @param integer $reportid ID of the report
     *
     * @return string Human readable description of the restriction
     */
    public function text_restriction($title, $reportid) {
        global $DB;

        $settings = reportbuilder::get_all_settings($reportid, self::TYPE);

        return !empty($settings['completiontype']) ? $title.' - '.get_string('type'.$settings['completiontype'], 'ojt') : '';
    }


    /**
     * Adds form elements required for this content restriction's settings page
     *
     * @param object &$mform Moodle form object to modify (passed by reference)
     * @param integer $reportid ID of the report being adjusted
     * @param string $title Name of the field the restriction is acting on
     */
    public function form_template(&$mform, $reportid, $title) {
        // get current settings
        $enable = reportbuilder::get_setting($reportid, self::TYPE, 'enable');
        $completiontype = reportbuilder::get_setting($reportid, self::TYPE, 'completiontype');

        $mform->addElement('header', 'ojt_completion_type_header',
            get_string('showbyx', 'totara_reportbuilder', lcfirst($title)));
        $mform->setExpanded('ojt_completion_type_header');
        $mform->addElement('checkbox', 'ojt_completion_type_enable', '',
            get_string('completiontypeenable', 'rb_source_ojt_completion'));
        $mform->setDefault('ojt_completion_type_enable', $enable);
        $mform->disabledIf('ojt_completion_type_enable', 'contentenabled', 'eq', 0);
        $radiogroup = array();
        $radiogroup[] =& $mform->createElement('radio', 'ojt_completion_type_completiontype',
            '', get_string('type'.OJT_CTYPE_OJT, 'ojt'), OJT_CTYPE_OJT);
        $radiogroup[] =& $mform->createElement('radio', 'ojt_completion_type_completiontype',
            '', get_string('type'.OJT_CTYPE_TOPIC, 'ojt'), OJT_CTYPE_TOPIC);
        $mform->addGroup($radiogroup, 'ojt_completion_type_completiontype_group',
            get_string('includecompltyperecords', 'rb_source_ojt_completion'), \html_writer::empty_tag('br'), false);
        $mform->setDefault('ojt_completion_type_completiontype', $completiontype);
        $mform->disabledIf('ojt_completion_type_completiontype_group', 'contentenabled',
            'eq', 0);
        $mform->disabledIf('ojt_completion_type_completiontype_group', 'ojt_completion_type_enable',
            'notchecked');
    }


    /**
     * Processes the form elements created by {@link form_template()}
     *
     * @param integer $reportid ID of the report to process
     * @param object $fromform Moodle form data received via form submission
     *
     * @return boolean True if form was successfully processed
     */
    public function form_process($reportid, $fromform) {
        $status = true;

        // enable checkbox option
        $enable = (isset($fromform->ojt_completion_type_enable) &&
            $fromform->ojt_completion_type_enable) ? 1 : 0;
        $status = $status && reportbuilder::update_setting($reportid, self::TYPE,
                'enable', $enable);

        // recursive radio option
        $recursive = isset($fromform->ojt_completion_type_completiontype) ?
            $fromform->ojt_completion_type_completiontype : 0;
        $status = $status && reportbuilder::update_setting($reportid, self::TYPE,
                'completiontype', $recursive);

        return $status;
    }
}