<?php

namespace auth_catadmin\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Suspends users after 1 day of inactivity.
 *
 * @package auth_catadmin\task
 */
class suspend_users extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('tasksuspendusers', 'auth_catadmin');
    }

    public function execute($force = false) {
        global $DB, $CFG;
        $DB->execute("UPDATE {user} SET suspended = 1 WHERE auth = 'catadmin' AND lastaccess < ? AND suspended = 0", array(strtotime('-1 day')));

        $admins = explode(',', $CFG->siteadmins);
        $users = $DB->get_records_sql("SELECT id FROM {user} WHERE auth = 'catadmin' AND lastaccess < ?", array(strtotime('-1 week')));
        foreach ($users as $user) {
            $key = array_search($user->id, $admins);
            if ($key !== false && $key !== null) {
                unset($admins[$key]);
            }
        }
        set_config('siteadmins', implode(',', $admins));

        return true;
    }
}
