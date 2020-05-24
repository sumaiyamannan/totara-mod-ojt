<?php

/**
 * HWRHAS-160
 *
 * @package    package
 * @subpackage sub_package
 * @copyright  &copy; 2019 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

namespace mod_ojt\task;

class archive extends \core\task\scheduled_task {
    
    public function get_name() {
        return get_string('archiveojt', 'mod_ojt');
    }
    
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot . '/mod/ojt/archive/archivelib.php');
        
        $completed_ojts = \mod_ojt\ojt_get_completed_ojts();
        if(!empty($completed_ojts)) {
            foreach ($completed_ojts as $ojt) {
                \mod_ojt\ojt_archive_and_add_pdf_file_to_evidence($ojt->ojtid, $ojt->userid);
            }
        }
    }
}