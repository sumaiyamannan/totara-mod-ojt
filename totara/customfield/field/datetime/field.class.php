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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage customfield
 */


class customfield_datetime extends customfield_base {

    /**
     * Handles editing datetime fields
     *
     * @param object moodleform instance
     */
    function edit_field_add(&$mform) {
        // Check if the field is required
        if ($this->field->required) {
            $optional = false;
        } else {
            $optional = true;
        }

        $attributes = array(
            'startyear' => $this->field->param1,
            'stopyear'  => $this->field->param2,
            'timezone'  => 99,
            'optional'  => $optional
        );

        // Check if they wanted to include time as well
        if (!empty($this->field->param3)) {
            $mform->addElement('date_time_selector', $this->inputname, format_string($this->field->fullname), $attributes);
        } else {
            $mform->addElement('date_selector', $this->inputname, format_string($this->field->fullname), $attributes);
        }

        $mform->setDefault($this->inputname, time());
    }

    /**
     * Display the data for this field
     */
    static function display_item_data($data, $extradata=array()) {
        $data = intval($data);

        // Check if time was specifieid with a sneaky sneaky little hack :)
        if (date('G', $data) != 0) { // 12:00 am - assume no time was saved
            $format = get_string('strftimedaydatetime', 'langconfig');
        } else {
            $format = get_string('strftimedate', 'langconfig');
        }

        // Check if a date has been specified
        if (empty($data)) {
            return get_string('notset', 'totara_customfield');
        } else {
            return userdate($data, $format);
        }
    }

    /**
     * Changes the customfield value from a file data to the key and value.
     *
     * @param  object $syncitem The original syncitem to be processed.
     * @return object The syncitem with the customfield data processed.
     */
    public function sync_filedata_preprocess($syncitem) {
        global $CFG;

        $value = $syncitem->{$this->field->shortname};
        unset($syncitem->{$this->field->shortname});

        // Parse using $CFG->csvdateformat if set, or default if not set.
        $csvdateformat = (isset($CFG->csvdateformat)) ? $CFG->csvdateformat : get_string('csvdateformatdefault', 'totara_core');
        // If date can't be parsed, assume it is a unix timestamp and leave unchanged.
        $parsed_date = totara_date_parse_from_format($csvdateformat, $value, true);
        if ($parsed_date) {
            $value = $parsed_date;
        }
        $syncitem->{$this->inputname} = $value;

        return $syncitem;
    }
}