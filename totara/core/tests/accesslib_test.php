<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

class totara_core_accesslib_testcase extends advanced_testcase {
    public function test_capability_names() {
        global $DB;
        $capabilities = $DB->get_records('capabilities', array());
        foreach ($capabilities as $capability) {
            $name = get_capability_string($capability->name);
            $this->assertDebuggingNotCalled("Debugging not expected when getting name of capability {$capability->name}");
            $this->assertNotContains('???', $name, "Unexpected problem when getting name of capability {$capability->name}");
        }
    }
}