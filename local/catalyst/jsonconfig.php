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
 * JSON configuration
 *
 * @package    local_catalyst
 * @author     Pierre Guinoiseau <pierre.guinoiseau@catalyst.net.nz>
 * @copyright  2011 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('locallib.php');
require_once('jsonconfig_form.php');

admin_externalpage_setup('catalystjsonconfig');

// The form works in 2 steps: submission and review

// Use and check the review form first
$form = new jsonconfig_form(null, array('review' => optional_param('review', false, PARAM_BOOL)));

if ($form->is_cancelled()) {
    // Review cancelled, redirect to the first form
    redirect($PAGE->url);
} elseif ($form->is_submitted() && $form->is_validated()) {
    $data = $form->get_data();
    if ($data->reviewed) {
        // Changes reviewed, save the new configuration!
        local_catalyst_import_config_from_json($data->jsonconfig);
        redirect($PAGE->url, get_string('changessaved'));
        exit;
    } else {
        // Compare the imported configuration with the current one
        $config_diff = local_catalyst_diff_config_with_json($data->jsonconfig);

        // No changes? Redirect to the first form
        if (empty($config_diff['core']) && empty($config_diff['plugins'])) {
            redirect($PAGE->url, get_string('jsonconfig_nochanges', 'local_catalyst'));
        }

        // Display the review form
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('jsonconfig_name', 'local_catalyst'));

        $table = new html_table();
        $table->head = array(
            get_string('jsonconfig_key', 'local_catalyst'),
            get_string('jsonconfig_value', 'local_catalyst'),
            get_string('jsonconfig_new_value', 'local_catalyst'));

        echo html_writer::tag('h3', get_string('jsonconfig_review', 'local_catalyst'));

        $table->caption = 'core';
        $table->data = array();
        foreach($config_diff['core'] as $config) {
            $table->data[] = local_catalyst_diff_table_row($config);
        }
        echo html_writer::table($table);

        foreach($config_diff['plugins'] as $plugin => $plugin_config) {
            if (empty($plugin_config)) continue;
            $table->caption = $plugin;
            $table->data = array();
            foreach($plugin_config as $config) {
                $table->data[] = local_catalyst_diff_table_row($config);
            }
            echo html_writer::table($table);
        }

        // The new configuration will be marked as reviewed on submission
        $form->_form->setConstants(array('reviewed' => 1));

        $form->display();

        echo $OUTPUT->footer();
        exit;
    }
}

// Display the view/submission form
$form->set_data(array('jsonconfig' => local_catalyst_export_config_as_json()));
$form->_form->setConstants(array('review' => 1));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('jsonconfig_name', 'local_catalyst'));

$info = format_text(get_string('jsonconfig_intro', 'local_catalyst'), FORMAT_MARKDOWN);
echo $OUTPUT->box($info);

$form->display();

echo $OUTPUT->footer();
