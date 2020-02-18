<?php

defined('MOODLE_INTERNAL') || die();

global $catadminsaml, $CFG, $SITE, $SESSION;

// Check for https login.
$wwwroot = $CFG->wwwroot;
if (!empty($CFG->loginhttps)) {
    $wwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

$config = [];

// Case for specifying no $SESSION IdP, select the first configured IdP as the default.
$arr = array_reverse($catadminsaml->metadataentities);
$metadataentities = array_pop($arr);
$idpentity = array_pop($metadataentities);
$idp = md5($idpentity->entityid);

if (!empty($SESSION->catadminidp)) {
    foreach ($catadminsaml->metadataentities as $idpentities) {
        foreach ($idpentities as $md5entityid => $idpentity) {
            if ($SESSION->catadminidp === $md5entityid) {
                $idp = $idpentity->entityid;
                break 2;
            }
        }
    }
}

$config[$catadminsaml->spname] = [
    'saml:SP',
    'entityID' => "$wwwroot/auth/catadmin/sp/metadata.php",
    'discoURL' => null,
    'idp' => $idp,
    'NameIDPolicy' => "urn:oasis:names:tc:SAML:1.1:nameid-format:unspecified",
    'OrganizationName' => array(
        'en' => $SITE->shortname,
    ),
    'OrganizationDisplayName' => array(
        'en' => $SITE->fullname,
    ),
    'OrganizationURL' => array(
        'en' => $CFG->wwwroot,
    ),
    'privatekey' => $catadminsaml->spname . '.pem',
    'privatekey_pass' => get_config('auth_catadmin', 'privatekeypass'),
    'certificate' => $catadminsaml->spname . '.crt',
    'sign.logout' => true,
    'redirect.sign' => true,
    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
];
