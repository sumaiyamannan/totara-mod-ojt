<?php 

/**
 * Comment
 *
 * @package    block
 * @subpackage ojt
 * @copyright  &copy; 2017 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */


defined('MOODLE_INTERNAL') || die();

class block_ojt_edit_form extends block_edit_form {
    
    protected function specific_definition($mform) {
        
        $block = block_instance('ojt', $this->block->instance);
        
        $mform->addElement('header', 'configheader', get_string('whattodisplay', 'block_ojt'));
        
        $mform->addElement('text', 'config_displayname', get_string('displayname', 'block_ojt'));
        $mform->setType('config_displayname', PARAM_TEXT);
        $mform->addHelpButton('config_displayname' , 'displayname', 'block_ojt');
    }
    
    public function get_data() {
        return parent::get_data();
    }
    
    public function set_data($defaults) {
        parent::set_data($defaults);
    }
}
