<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot.'/cache/locallib.php');
require_once($CFG->dirroot.'/cache/forms.php');
require_once($CFG->dirroot.'/local/clustercache/locallib.php');

function xmldb_local_clustercache_install() {
    return clustercache_setup_cache();
}
