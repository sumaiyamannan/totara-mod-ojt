<?php
/**
 * Version information
 *
 * @package    auth_catadmin
 * @copyright  Alex Morris <alex.morris@catadmin.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2020010901;    // The current plugin version (Date: YYYYMMDDXX).
$plugin->release   = 2020010901;    // Match release exactly to version.
$plugin->requires  = 2014051200;    // Moodle version 2.7

$plugin->component = 'auth_catadmin';  // Full name of the plugin (used for diagnostics).
$plugin->maturity  = MATURITY_STABLE;
$plugin->dependencies = array(
    'auth_saml2' => 2019062601
);
