<?php

/**
 * Comment
 *
 * @package
 * @subpackage
 * @copyright  &copy; 2016 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

namespace local_hrimport;

defined('MOODLE_INTERNAL') || die();
require_once $CFG->libdir.'/formslib.php';
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');

class csv_upload extends \moodleform {
    function definition (){
        global $CFG;
        $mform =& $this->_form;

        $elements = totara_sync_get_elements($onlyenabled=true);
        if (!count($elements)) {
            $mform->addElement('html', html_writer::tag('p', get_string('noenabledelements', 'tool_totara_sync')));
            return;
        }

        foreach ($elements as $e) {
            $name = $e->get_name();
            $mform->addElement('header', "header_{$name}", get_string("displayname:{$name}", 'tool_totara_sync'));
            $mform->setExpanded("header_{$name}");

            try {
                $source = $e->get_source();
            } catch (\totara_sync_exception $e) {
                $link = "{$CFG->wwwroot}/admin/tool/totara_sync/admin/elementsettings.php?element={$name}";
                $mform->addElement('html', \html_writer::tag('p',get_string('nosourceconfigured', 'tool_totara_sync', $link)));
                continue;
            }

            if (!$source->uses_files()) {
                $mform->addElement('html', html_writer::tag('p', get_string('sourcedoesnotusefiles', 'tool_totara_sync')));
                continue;
            }

            $mform->addElement('filepicker', $name, get_string('displayname:'.$source->get_name(), 'tool_totara_sync'), 'size="40"');
        }

        $this->add_action_buttons(false, get_string('upload'));
    }

    function setElementError($element, $message) {
        $this->_form->setElementError($element, $message);
    }
}