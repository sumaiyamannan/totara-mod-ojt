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
 * @package totara_core
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Tests for Totara functionality added to DML database drivers.
 */
class totara_core_dml_testcase extends database_driver_testcase {
    public function test_sql_group_concat() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('orderby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_field('valtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valint', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $text = str_repeat('š', 3999);

        $DB->insert_record($tablename, (object)array('orderby' => 15, 'groupid' => 12, 'valchar' => 'áéíóú', 'valtext' => $text.'1', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' => 20, 'groupid' => 12, 'valchar' => '12345', 'valtext' => $text.'2', 'valint' => 2));
        $DB->insert_record($tablename, (object)array('orderby' =>  5, 'groupid' => 12, 'valchar' =>    null, 'valtext' => $text.'3', 'valint' => 3));
        $DB->insert_record($tablename, (object)array('orderby' => 10, 'groupid' => 12, 'valchar' => 'abcde', 'valtext' =>      null, 'valint' => 4));
        $DB->insert_record($tablename, (object)array('orderby' => 12, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' =>      null, 'valint' => 5));
        $DB->insert_record($tablename, (object)array('orderby' =>  4, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' => $text.'6', 'valint' => 6));
        $DB->insert_record($tablename, (object)array('orderby' =>  8, 'groupid' => 24, 'valchar' => 'abcde', 'valtext' => $text.'7', 'valint' => 7));
        $DB->insert_record($tablename, (object)array('orderby' =>  6, 'groupid' => 36, 'valchar' => 'a\+1_', 'valtext' => $text.'8', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' =>  3, 'groupid' => 36, 'valchar' =>    null, 'valtext' =>      null, 'valint' => 9));

        $sloppymssql = false;
        if ($DB->get_dbfamily() === 'mssql') {
            $serverinfo = $DB->get_server_info();
            if (version_compare($serverinfo['version'], '14', '<')) {
                $sloppymssql = true;
            }
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valchar', ',', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(3, explode(',', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345,áéíóú,abcde', $records[12]->grpconcat);
            $this->assertEquals('abc12,abcde,abc12', $records[24]->grpconcat);
            $this->assertEquals('a\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', ',', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode(',', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345,ÁÉÍÓÚ,ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12,ABCDE,ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', "'", 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode("'", $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode("'", $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(1, explode("'", $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345\'ÁÉÍÓÚ\'ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12\'ABCDE\'ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('UPPER(valchar)', '\\', 'orderby DESC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('\\', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(3, explode('\\', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertCount(2, explode('\\', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('12345\ÁÉÍÓÚ\ABCDE', $records[12]->grpconcat);
            $this->assertEquals('ABC12\ABCDE\ABC12', $records[24]->grpconcat);
            $this->assertEquals('A\+1_', $records[36]->grpconcat);
        }

        $sql = 'SELECT groupid, ' . $DB->sql_group_concat("COALESCE(valchar, '-')", '|', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(4, explode('|', $records[12]->grpconcat));
        $this->assertContains('-', $records[12]->grpconcat);
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(3, explode('|', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertCount(2, explode('|', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertContains('-', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('-|abcde|áéíóú|12345', $records[12]->grpconcat);
            $this->assertEquals('abc12|abcde|abc12', $records[24]->grpconcat);
            $this->assertEquals('-|a\+1_', $records[36]->grpconcat);
        }

        // Verify integers are cast to varchars.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals('3:4:2', $records[12]->grpconcat);
            $this->assertEquals('6:7:5', $records[24]->grpconcat);
            $this->assertEquals('9', $records[36]->grpconcat);
        }

        // Verify texts are cast to varchars.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valtext', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals($text.'3:'.$text.'1:'.$text.'2', $records[12]->grpconcat);
            $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
            $this->assertEquals($text.'8', $records[36]->grpconcat);
        }

        // Make sure the orders are independent.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valtext', ':', 'orderby ASC') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid ORDER BY groupid DESC';
        $records = $DB->get_records_sql($sql);
        $this->assertSame(array(36, 24, 12), array_keys($records));
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        if (!$sloppymssql) {
            // All decent databases can order the values properly.
            $this->assertEquals($text.'3:'.$text.'1:'.$text.'2', $records[12]->grpconcat);
            $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
            $this->assertEquals($text.'8', $records[36]->grpconcat);
        }

        // Test without $orderby param, the order is not defined.
        $sql = 'SELECT groupid, ' . $DB->sql_group_concat('valint', ':') . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);

        $dbman->drop_table($table);
    }

    public function test_sql_group_concat_unique() {
        $DB = $this->tdb;
        $dbman = $DB->get_manager();

        $tablename = 'test_table';
        $table = new xmldb_table('test_table');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('orderby', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('groupid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('valchar', XMLDB_TYPE_CHAR, '225', null, null, null, null);
        $table->add_field('valtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('valint', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_table($table);

        $text = str_repeat('š', 3999);

        $DB->insert_record($tablename, (object)array('orderby' => 15, 'groupid' => 12, 'valchar' => 'áéíóú', 'valtext' => $text.'1', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' => 20, 'groupid' => 12, 'valchar' => '12345', 'valtext' => $text.'2', 'valint' => 2));
        $DB->insert_record($tablename, (object)array('orderby' =>  5, 'groupid' => 12, 'valchar' =>    null, 'valtext' => $text.'3', 'valint' => 3));
        $DB->insert_record($tablename, (object)array('orderby' => 10, 'groupid' => 12, 'valchar' => 'abcde', 'valtext' =>      null, 'valint' => 4));
        $DB->insert_record($tablename, (object)array('orderby' => 12, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' =>      null, 'valint' => 5));
        $DB->insert_record($tablename, (object)array('orderby' =>  4, 'groupid' => 24, 'valchar' => 'abc12', 'valtext' => $text.'6', 'valint' => 6));
        $DB->insert_record($tablename, (object)array('orderby' =>  8, 'groupid' => 24, 'valchar' => 'abcde', 'valtext' => $text.'7', 'valint' => 7));
        $DB->insert_record($tablename, (object)array('orderby' =>  6, 'groupid' => 36, 'valchar' => 'a\+1_', 'valtext' => $text.'8', 'valint' => null));
        $DB->insert_record($tablename, (object)array('orderby' =>  3, 'groupid' => 36, 'valchar' =>    null, 'valtext' =>      null, 'valint' => 9));

        $grpconcat = $DB->sql_group_concat_unique('valchar', ',');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(2, explode(',', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertEquals('abc12,abcde', $records[24]->grpconcat);
        $this->assertEquals('a\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', ',');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(',', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode(',', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(1, explode(',', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12,ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', "'");
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode("'", $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode("'", $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(1, explode("'", $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12\'ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique('UPPER(valchar)', '\\');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode('\\', $records[12]->grpconcat));
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('ÁÉÍÓÚ', $records[12]->grpconcat);
        $this->assertContains('ABCDE', $records[12]->grpconcat);
        $this->assertCount(2, explode('\\', $records[24]->grpconcat));
        $this->assertContains('ABC12', $records[24]->grpconcat);
        $this->assertContains('ABCDE', $records[24]->grpconcat);
        $this->assertCount(2, explode('\\', $records[36]->grpconcat));
        $this->assertContains('A\+1_', $records[36]->grpconcat);
        $this->assertEquals('ABC12\ABCDE', $records[24]->grpconcat);
        $this->assertEquals('A\+1_', $records[36]->grpconcat);

        $grpconcat = $DB->sql_group_concat_unique("COALESCE(valchar, '-')", '|');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(4, explode('|', $records[12]->grpconcat));
        $this->assertContains('-', $records[12]->grpconcat);
        $this->assertContains('12345', $records[12]->grpconcat);
        $this->assertContains('áéíóú', $records[12]->grpconcat);
        $this->assertContains('abcde', $records[12]->grpconcat);
        $this->assertCount(2, explode('|', $records[24]->grpconcat));
        $this->assertContains('abc12', $records[24]->grpconcat);
        $this->assertContains('abcde', $records[24]->grpconcat);
        $this->assertCount(2, explode('|', $records[36]->grpconcat));
        $this->assertContains('a\+1_', $records[36]->grpconcat);
        $this->assertContains('-', $records[36]->grpconcat);

        // Verify integers are cast to varchars.
        $grpconcat = $DB->sql_group_concat_unique('valint', ':');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertContains('3', $records[12]->grpconcat);
        $this->assertContains('4', $records[12]->grpconcat);
        $this->assertContains('2', $records[12]->grpconcat);
        $this->assertCount(3, explode(':', $records[24]->grpconcat));
        $this->assertContains('6', $records[24]->grpconcat);
        $this->assertContains('7', $records[24]->grpconcat);
        $this->assertContains('5', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertContains('9', $records[36]->grpconcat);
        $this->assertEquals('9', $records[36]->grpconcat);

        // Verify texts are cast to varchars.
        $grpconcat = $DB->sql_group_concat_unique('valtext', ':');
        $sql = 'SELECT groupid, ' . $grpconcat . ' AS grpconcat FROM {' . $tablename . '} GROUP BY groupid';
        $records = $DB->get_records_sql($sql);
        $this->assertCount(3, $records);
        $this->assertCount(3, explode(':', $records[12]->grpconcat));
        $this->assertSame(23999, strlen($records[12]->grpconcat));
        $this->assertContains($text.'3', $records[12]->grpconcat);
        $this->assertContains($text.'1', $records[12]->grpconcat);
        $this->assertContains($text.'2', $records[12]->grpconcat);
        $this->assertCount(2, explode(':', $records[24]->grpconcat));
        $this->assertSame(15999, strlen($records[24]->grpconcat));
        $this->assertContains($text.'6', $records[24]->grpconcat);
        $this->assertContains($text.'7', $records[24]->grpconcat);
        $this->assertCount(1, explode(':', $records[36]->grpconcat));
        $this->assertSame(7999, strlen($records[36]->grpconcat));
        $this->assertContains($text.'8', $records[36]->grpconcat);
        $this->assertEquals($text.'6:'.$text.'7', $records[24]->grpconcat);
        $this->assertEquals($text.'8', $records[36]->grpconcat);

        $dbman->drop_table($table);
    }
}
