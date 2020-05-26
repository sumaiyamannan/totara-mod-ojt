<?php

/**
 * Comment
 *
 * @package    local
 * @subpackage hrimport
 * @copyright  &copy; 2016 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

if ($hassiteconfig) {
    $ADMIN->add('tool_totara_sync', new admin_category('syncvalidate', get_string('validation', 'local_hrimport')));
    $ADMIN->add('syncvalidate',
            new admin_externalpage('hrdatasanitiserupload', get_string('upload_csv', 'local_hrimport'),
                    "$CFG->wwwroot/local/hrimport/upload.php")
                );
}
