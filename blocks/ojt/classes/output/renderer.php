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

namespace block_ojt\output;

class renderer extends \plugin_renderer_base {
    
    function render_ojt_activities($ojts) {
        global $CFG;
        
        if(empty($ojts)) {
            return \html_writer::div(
                    \html_writer::div(get_string('noactivities', 'block_ojt'), 'alert alert-info')
                ,'no-activities');
        }
        
        $table = new \html_table();
        $table->attributes = array('class' => 'table ojt-list'); 
        $table->head = array(
            get_string('ojtname', 'block_ojt'),
            get_string('coursename', 'block_ojt')
        );

        foreach ($ojts as $ojt) {
            $data = array();
            $ojtlink = new \moodle_url($CFG->wwwroot . '/mod/ojt/view.php', array('id' => $ojt->cmid));
            $courselink = new \moodle_url($CFG->wwwroot . '/course/view.php', array('id' => $ojt->courseid));
            $data[] = \html_writer::link($ojtlink, $ojt->ojtname);
            $data[] = \html_writer::link($courselink, $ojt->coursename);
                    
            $table->data[] = $data;
        }
        return \html_writer::table($table);   
    }
}
