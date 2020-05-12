<?php

/**
 * Test page for SAML
 *
 * @package    auth_saml2
 * @copyright  Brendan Heywood <brendan@catadmin-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require('../setup.php');

// First setup the PATH_INFO because that's how SSP rolls.
$_SERVER['PATH_INFO'] = '/' . $catadminsaml->spname;

try {
    require($CFG->dirroot.'/auth/saml2/extlib/simplesamlphp/modules/saml/www/sp/saml2-logout.php');
} catch (Exception $e) {
    // TODO SSPHP uses Exceptions for handling valid conditions, so a succesful
    // logout is an Exception. This is a workaround to just go back to the home
    // page but we should probably handle SimpleSAML_Error_Error similar to how
    // extlib/simplesamlphp/www/_include.php handles it.
    redirect(new moodle_url('/'));
}
