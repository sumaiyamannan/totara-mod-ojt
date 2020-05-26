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


class block_ojt extends block_base {
    
    public function init() {
        $this->title = get_string('pluginname', 'block_ojt');
    }
    
    public function has_config() {
        return true;
    }
    
    public function instance_allow_multiple() {
        return true;
    }
    
    public function applicable_formats() {
        return array('all' => true);
    }
    
    public function specialization() {
        if(!$this->config) {
            return;
        }
        if($this->config->displayname) {
            $this->title = $this->config->displayname;
        }
    }
    
    public function get_content() {
        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot. '/blocks/ojt/classes/output/renderer.php');
        require_once($CFG->dirroot. '/blocks/ojt/lib.php');
        
        $renderer = $PAGE->get_renderer('block_ojt');
        
        $ojts = block_ojt_get_ojt_activities($USER->id);
        
        if ($this->content !== null) {
            return $this->content;
        }
        
        $this->content = new stdClass();
        $this->content->text = !empty($ojts) ? $renderer->render_ojt_activities($ojts) : '';                        
        $this->content->footer = '';

        return $this->content;
    }
    
        
}
