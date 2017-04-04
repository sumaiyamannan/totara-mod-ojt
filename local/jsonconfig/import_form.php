<?php

// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * JSON configuration import form
 *
 * @package    local_jsonconfig
 * @author     Pierre Guinoiseau <pierre.guinoiseau@catalyst.net.nz>
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');
require_once('locallib.php');

class local_jsonconfig_import_form extends moodleform {

    /**
     * Define JSON import form.
     */
    protected function definition() {
        $mform = $this->_form;

        $review = !empty($this->_customdata['review']);

        $jsonconfig_attrs = array('cols' => 80, 'rows' => 25);
        $submit = 'review_import';

        // Make the JSON config readonly if in review
        if ($review) {
            $jsonconfig_attrs['readonly'] = 'readonly';
            $submit = 'import';
        }

        $mform->addElement('textarea', 'jsonconfig', get_string('json_field', 'local_jsonconfig'),
                           $jsonconfig_attrs);
        $mform->setType('jsonconfig', PARAM_RAW);

        // Set to 1 in the submission form to go the review form next
        $mform->addElement('hidden', 'review');
        $mform->setType('review', PARAM_BOOL);
        $mform->setDefault('review', 0);

        // Set to 1 in the review form to save the configuration
        $mform->addElement('hidden', 'reviewed');
        $mform->setType('reviewed', PARAM_BOOL);
        $mform->setDefault('reviewed', 0);

        // The cancel button is enabled if in review
        $this->add_action_buttons($review, get_string($submit, 'local_jsonconfig'));
    }

    /**
     * Validate JSON syntax
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        json_decode($data['jsonconfig']);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $errors['jsonconfig'] = get_string('invalid', 'local_jsonconfig');
        }
        return $errors;
    }

    public function mark_as_reviewed() {
        $this->_form->setConstants(array('reviewed' => 1));
    }

    public function mark_for_review() {
        $this->_form->setConstants(array('review' => 1));
    }
}
