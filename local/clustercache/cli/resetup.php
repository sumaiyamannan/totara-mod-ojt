<?php
define('CLI_SCRIPT', true);
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/clustercache/locallib.php');

$result = clustercache_setup_cache ();
if ($result) {
    cli_writeln("Success");
    exit(0);
}

cli_problem("Failure");
exit(1);
