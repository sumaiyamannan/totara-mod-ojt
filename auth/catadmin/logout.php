<?php

require_once(__DIR__ . '/../../config.php');
require('setup.php');

// Generally this page is not used as the normal moodle logout_hook
// doe everything needed. This page is used when someone is logged
// into SAML, but not into Moodle, and needs to logout of SAML in
// order to properly re-log into Moodle. It's a rare edge case
// probably only used when configuring or testing but nice to have.

$auth = new SimpleSAML\Auth\Simple($catadminsaml->spname);

$auth->logout('/');