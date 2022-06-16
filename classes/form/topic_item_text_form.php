<?php
// This file is part of Moodle - http://moodle.org/
//
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
 * OJT topic item text form
 *
 * @package   mod_ojt
 * @author    Alex Morris <alex.morris@catalyst.net.nz
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_ojt\form;

defined('MOODLE_INTERNAL') || die();

/**
 * OJT topic item text form
 *
 * @package   mod_ojt
 * @author    Alex Morris <alex.morris@catalyst.net.nz
 * @copyright Catalyst IT
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class topic_item_text_form extends topic_item_form {
    function definition() {
        parent::definition();
        $mform =& $this->_form;

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_INT);
        $mform->setDefault('type', OJT_ITEM_TYPE_TEXT);

        $this->add_action_buttons(false);
    }

}
