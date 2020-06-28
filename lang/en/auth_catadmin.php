<?php
/**
 * @package    auth_catadmin
 * @copyright  Alex Morris <alex.morris@catadmin.net.nz>
 */

$string['pluginname'] = 'Catadmin Authentication';
$string['auth_catadmindescription'] = 'Authentication plugin for Catalyst IT Staff';

$string['debug'] = 'Debugging';
$string['debug_help'] = '<p>This adds extra debugging to the normal moodle log | <a href=\'{$a}\'>View SSP config</a></p>';

$string['select_idp_button'] = 'IdP Login';

$string["privacy:no_data_reason"] = "The Catadmin authentication plugin does not store any personal data.";

$string['idpnamedefault'] = 'Login via SAML2';

$string['noattribute'] = 'You have logged in successfully but we could not find your \'{$a}\' attribute to associate you to an account in Moodle.';
$string['noidpfound'] = 'The IdP \'{$a}\' was not found as a configured IdP.';
$string['nouser'] = 'You have logged in successfully as \'{$a}\' but do not have an account in Moodle.';

$string['idpmetadata_badurl'] = 'Invalid metadata at {$a}';
$string['taskmetadatarefresh'] = 'Metadata refresh task';
$string['tasksuspendusers'] = 'Suspend users task';

$string['emailtaken'] = 'The email {$a} is already in use.';
$string['incorrectauthtype'] = 'You attempted to log in but your user account has a different authentication type.';
$string['nouser'] = 'The user {$a} does not exist in moodle.';
$string['noaccess'] = 'You do not have access to this site. You are missing group {$a}.';
