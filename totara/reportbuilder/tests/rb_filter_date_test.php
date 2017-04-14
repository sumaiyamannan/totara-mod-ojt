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
 * @author Rob tyler <rob.tyler@totaralearning.com>
 * @package totara_reportbuilder
 *
 * Unit/functional tests to check Record of Learning: Objectives reports caching
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

global $CFG;
require_once($CFG->dirroot . '/totara/reportbuilder/filters/lib.php');
require_once($CFG->dirroot . '/totara/reportbuilder/filters/date.php');

class totara_reportbuilder_rb_filter_date_testcase extends advanced_testcase {
    use totara_reportbuilder\phpunit\report_testing;

    private $today_timestamp;
    private $filter;

    protected function tearDown() {
        $this->today_timestamp = null;
        $this->filter = null;
        parent::tearDown();
    }

    protected function setUp() {
        parent::setup();

        // Set up the basic User report.
        $report_id = $this->create_report('user', 'Test User Report');
        $report = new reportbuilder($report_id, null, false, null, null, true);

        // Check the report has been created okay.
        $this->assertNotFalse($report_id);
        $this->assertTrue(is_object($report));

        // Instantiate the date filter.
        $this->filter = new rb_filter_date('user', 'firstaccess', 0, 0, $report);

        // Set midnight today so we can do the days relative to today tests.
        $this->today_timestamp = strtotime('00:00:00');
        // Set the number of days for the days before and after tests.
    }

    /**
     * Test the sql created for the 'is after' part of the filter.
     */
    public function test_get_sql_filter_after() {
        $this->resetAfterTest();

        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 1483228800,
            'after_applied' => true,
            'before' => 0,
            'daysafter' => 0,
            'daysbefore' => 0,
            'notset' => 0
        ));

        $this->assertEquals('base.firstaccess != :uq_fdnotnull_1 AND base.firstaccess >= :uq_fdafter_1', $sql);
        $this->assertEquals(array('uq_fdnotnull_1' => 0, 'uq_fdafter_1' => 1483228800), $params);
    }

    /**
     * Test the sql created for the 'is before' part of the filter.
     */
    public function test_get_sql_filter_before() {
        $this->resetAfterTest();

        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 0,
            'before' => 1483228800,
            'before_applied' => true,
            'daysafter' => 0,
            'daysbefore' => 0,
            'notset' => 0
        ));

        $this->assertEquals('base.firstaccess != :uq_fdnotnull_2 AND base.firstaccess < :uq_fdbefore_1', $sql);
        $this->assertEquals(array('uq_fdnotnull_2' => 0, 'uq_fdbefore_1' => 1483228800), $params);
    }

    /**
     * Test the sql created for the 'is after' and 'is before' parts of the filter together.
     */
    public function test_get_sql_filter_after_before() {
        $this->resetAfterTest();

        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 1483228800,
            'after_applied' => true,
            'before' => 1491001200,
            'before_applied' => true,
            'daysafter' => 0,
            'daysbefore' => 0,
            'notset' => 0
        ));

        $this->assertEquals('base.firstaccess != :uq_fdnotnull_3 AND base.firstaccess >= :uq_fdafter_2 AND base.firstaccess < :uq_fdbefore_2', $sql);
        $this->assertEquals(array('uq_fdnotnull_3' => 0, 'uq_fdafter_2' => 1483228800, 'uq_fdbefore_2' => 1491001200), $params);
    }

    /**
     * Test the sql created for the 'is days after today' part of the filter.
     */
    public function test_get_sql_filter_days_after() {
        $this->resetAfterTest();

        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 0,
            'before' => 0,
            'daysafter' => 10,
            'daysbefore' => 0,
            'notset' => 0
        ));

        $this->assertEquals('base.firstaccess >= ' . $this->today_timestamp . ' AND base.firstaccess <= :uq_fdaysafter_1', $sql);
        $this->assertEquals(array('uq_fdnotnull_4' => 0, 'uq_fdaysafter_1' => $this->today_timestamp + (10 * DAYSECS)), $params);
    }

    /**
     * Test the sql created for the 'is days before today' part of the filter.
     */
    public function test_get_sql_filter_days_before() {
        $this->resetAfterTest();

        // But then, go straight to the get_sql_filter method for testing.
        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 0,
            'before' => 0,
            'daysafter' => 0,
            'daysbefore' => 10,
            'notset' => 0
        ));

        $this->assertEquals('base.firstaccess <= ' . $this->today_timestamp . ' AND base.firstaccess >= :uq_fdaysbefore_1', $sql);
        $this->assertEquals(array('uq_fdnotnull_5' => 0, 'uq_fdaysbefore_1' => $this->today_timestamp - (10 * DAYSECS)), $params);
    }

    /**
     * Test the sql created for the 'is days after today' and 'before' parts of the filter.
     */
    public function test_get_sql_filter_days_after_before() {
        $this->resetAfterTest();

        // But then, go straight to the get_sql_filter method for testing.
        list ($sql, $params) = $this->filter->get_sql_filter(array(
            'after' => 0,
            'before' => 0,
            'daysafter' => 10,
            'daysbefore' => 10,
            'notset' => 0
        ));

        $this->assertEquals('(base.firstaccess >= ' . $this->today_timestamp . ' AND base.firstaccess <= :uq_fdaysafter_2
                OR base.firstaccess <= ' . $this->today_timestamp . ' AND base.firstaccess >= :uq_fdaysbefore_2)', $sql);
        $this->assertEquals(array('uq_fdnotnull_6' => 0,
            'uq_fdaysafter_2' => $this->today_timestamp + (10 * DAYSECS),
            'uq_fdaysbefore_2' => $this->today_timestamp - (10 * DAYSECS)),
            $params
        );
    }
}
