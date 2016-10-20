<?php
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/cache/locallib.php');
require_once($CFG->dirroot.'/cache/forms.php');
require_once($CFG->dirroot.'/local/clustercache/locallib.php');
require_login(0, false);
$context = context_system::instance();
$PAGE->set_url("$CFG->httpswwwroot/login/index.php");
$PAGE->set_context($context);

require_capability('moodle/site:config', $context);
$result = clustercache_setup_cache ();
if ($result) {
    print "Success<br/>\n";
} else {
    print "Failure<br/>\n";
}
