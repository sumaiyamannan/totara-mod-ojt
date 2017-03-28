<?php

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require(dirname(dirname(dirname(__FILE__))).'/config.php');
require($CFG->libdir.'/clilib.php');


$checker = \core\update\checker::instance();
$pluginman = core_plugin_manager::instance();

echo "\nChecking for updates... ";

$checker->fetch();
$installables = $pluginman->filter_installable($pluginman->available_updates());

$count = count($installables);

echo $count." found\n\n";

if ($count == 0) {
    exit(0);
}

$options = array('a');

$index = 0;

foreach ($installables as $plugin) {
    $index++;
    printf("%2d) %s - %s\n", $index, $plugin->component, $plugin->name);

    $options[] = $index;
}

$input = cli_input("\n\nPlugins to upgrade (default all)? ", 'a', $options, false);

if ($input == 'a') {
    $install = array_keys(array_fill(1, $count, ''));
} else {
    $install = explode(' ', $input);
}

echo "\n";

foreach ($install as $index) {

    $plugin = array_slice($installables, $index - 1, 1, true);

    printf("%2d - Upgrading %s... ", $index, array_keys($plugin)[0]);
    $pluginman->install_plugins($plugin, true, true);
    echo "done.\n";
}
