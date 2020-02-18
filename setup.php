<?php

/**
 * Setup
 *
 * @package     auth_catadmin
 */
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/setuplib.php');

global $CFG, $catadminsaml;

if(isset($CFG->sslproxy) && $CFG->sslproxy) {
    $_SERVER['SERVER_PORT'] = '443';
}

$catadminsaml = new auth_plugin_catadmin();
$catadminsaml->initialise();

$catadminsaml->get_catadmin_directory();
if (!file_exists($catadminsaml->certpem) || !file_exists($catadminsaml->certcrt)) {
    $error = create_catadmin_certificates($catadminsaml);
    if ($error) {
        error_log($error);
    }
}

SimpleSAML_Configuration::setConfigDir("$CFG->dirroot/auth/catadmin/config");
