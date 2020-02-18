<?php
/**
 * Setup functions
 *
 * @package    auth_catadmin
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/autoload.php');

global $CFG;

require_once("{$CFG->dirroot}/auth/catadmin/auth.php");

/**
 * Ensure that valid certificates exist.
 *
 * @copyright  Brendan Heywood <brendan@catadmin-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @param stdObj  $catadminsaml config object
 * @param array   $dn Certificate Distinguished name details
 * @param integer $numberofdays Certificate expirey period
 */
function create_catadmin_certificates($catadminsaml, $dn = false, $numberofdays = 3650) {
    global $SITE, $CFG;

    $opensslargs = array(
        'digest_alg' => 'SHA256',
    );
    if (array_key_exists('OPENSSL_CONF', $_SERVER)) {
        $opensslargs['config'] = $_SERVER['OPENSSL_CONF'];
    }

    if ($dn == false) {
        $supportuser = \core_user::get_support_user();

        if ($supportuser && !empty($supportuser->email)) {
            $email = $supportuser->email;
        } else if (isset($CFG->noreplyaddress) && !empty($CFG->noreplyaddress)) {
            $email = $CFG->noreplyaddress;
        } else {
            // Make sure that we get at least something to prevent failing of openssl_csr_new.
            $email = 'moodle@example.com';
        }

        // These are somewhat arbitrary and aren't really seen except inside
        // the auto created certificate used to sign saml requests.
        $dn = array(
            'commonName' => 'moodle',
            'countryName' => 'NZ',
            'localityName' => 'moodleville',
            'emailAddress' => $email,
            'organizationName' => $SITE->shortname ? $SITE->shortname : 'moodle',
            'stateOrProvinceName' => 'moodle',
            'organizationalUnitName' => 'moodle',
        );
    }

    // Ensure existing messages are dropped.
    $errors = array();
    while ($error = openssl_error_string()) {
        $errors[] = $error;
    }
    $privkeypass = get_config('auth_catadmin', 'privatekeypass');
    $privkey = openssl_pkey_new($opensslargs);
    $csr     = openssl_csr_new($dn, $privkey, $opensslargs);
    $sscert  = openssl_csr_sign($csr, null, $privkey, $numberofdays, $opensslargs);
    openssl_x509_export($sscert, $publickey);
    openssl_pkey_export($privkey, $privatekey, $privkeypass, $opensslargs);
    openssl_pkey_export($privkey, $privatekey, $privkeypass);
    $errors = array();
    while ($error = openssl_error_string()) {
        $errors[] = $error;
    }
    $errors = html_writer::alist($errors);

    // Write Private Key and Certificate files to disk.
    // If there was a generation error with either explode.
    if (empty($privatekey)) {
        return get_string('nullprivatecert', 'auth_catadmin') . $errors;
    }
    if (empty($publickey)) {
        return get_string('nullpubliccert', 'auth_catadmin') . $errors;
    }

    if ( !file_put_contents($catadminsaml->certpem, $privatekey) ) {
        return get_string('nullprivatecert', 'auth_catadmin');
    }
    if ( !file_put_contents($catadminsaml->certcrt, $publickey) ) {
        return get_string('nullpubliccert', 'auth_catadmin');
    }
}
