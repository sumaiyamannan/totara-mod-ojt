<?php

/**
 * Comment
 *
 * @package    package
 * @subpackage sub_package
 * @copyright  &copy; 2017 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

defined('MOODLE_INTERNAL') or die;

global $CFG;

function block_ojt_get_ojt_activities($userid) {
    global $DB;
    
    $config = get_config('block_ojt', 'displayforroles');
    $rolesfilter = " AND r.shortname IN ('teacher')";
    if(!empty($config)) {
        $accepted_roles_arr = explode(',', $config);
        $accepted_roles_arr = array_map('trim', $accepted_roles_arr);
        $stringForIN = "'" . implode("','", $accepted_roles_arr) . "'";

        $rolesfilter = " AND r.shortname IN ($stringForIN)";
    }
    
    // get roles here
    $sql = "SELECT DISTINCT ojt.id AS ojtid, ojt.name AS ojtname, c.fullname AS coursename, cm.id AS cmid, c.id AS courseid 
              FROM {user} u
              JOIN {role_assignments} ra 
                ON ra.userid = u.id
              JOIN {role} r 
                ON ra.roleid = r.id
              JOIN {context} con 
                ON ra.contextid = con.id
              JOIN {course} c 
                ON c.id = con.instanceid AND con.contextlevel = :coursecontext
              JOIN {course_modules} cm
                ON cm.course = c.id
              JOIN {modules} m
                ON cm.module = m.id AND m.name = 'ojt'
              JOIN {ojt} ojt
                ON ojt.id = cm.instance
             WHERE u.id = :userid ".$rolesfilter; 
    
    return $DB->get_records_sql($sql, array('userid' => $userid, 'coursecontext' => CONTEXT_COURSE));
}