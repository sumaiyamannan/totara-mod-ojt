<?php

/**
 * Module landing page.
 *
 * @package    auth_saml2
 * @author     Sam Chaffee
 * @copyright  Copyright (c) 2017 Blackboard Inc. (http://www.blackboard.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once('../setup.php');

// Tell SSP that we are on 443 if we are terminating SSL elsewhere.
if (!empty($CFG->sslproxy)) {
    $_SERVER['SERVER_PORT'] = '443';
}

require($CFG->dirroot.'/auth/saml2/extlib/simplesamlphp/www/module.php');
