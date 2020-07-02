<?php

defined('MOODLE_INTERNAL') || die();

global $CFG, $catadminsaml;

$wwwroot = $CFG->wwwroot;
if(!empty($CFG->loginhttps)) {
    $wwwroot = str_replace('http:', 'https:', $CFG->wwwroot);
}

$metadatasources = [];
foreach ($catadminsaml->metadataentities as $metadataurl => $idpentities) {
    $metadatasources[] = [
        'type' => 'xml',
        'file' => "$CFG->dataroot/catadmin/" . md5($metadataurl) . ".idp.xml"
    ];
}

$config = array(
    'baseurlpath' => $wwwroot . '/auth/catadmin/sp/',
    'certdir'           => $catadminsaml->get_catadmin_directory() . '/',
    'debug'             => $catadminsaml->config->debug ? true : false,
    'logging.level'     => $catadminsaml->config->debug ? SimpleSAML\Logger::DEBUG : SimpleSAML\Logger::ERR,
    'logging.handler'   => $catadminsaml->config->logtofile ? 'file' : 'errorlog',
    'loggingdir'        => $catadminsaml->config->logdir,
    'logging.logfile'   => 'simplesamlphp.log',
    'showerrors'        => $CFG->debugdisplay ? true : false,
    'errorreporting'    => false,
    'debug.validatexml' => false,
    'secretsalt'        => get_config('auth_catadmin', 'privatekeypass'),
    'technicalcontact_name'  => $CFG->supportname ? $CFG->supportname : 'Admin User',
    'technicalcontact_email' => $CFG->supportemail ? $CFG->supportemail : $CFG->noreplyaddress,
    'timezone' => class_exists('core_date') ? core_date::get_server_timezone() : null,

    'session.duration'          => 60 * 60 * 8, // 8 hours.
    'session.datastore.timeout' => 60 * 60 * 4,
    'session.state.timeout'     => 60 * 60,

    'session.authtoken.cookiename'  => 'MDL_SSP_AuthToken',
    'session.cookie.name'     => 'MDL_SSP_SessID',
    'session.cookie.path'     => $CFG->sessioncookiepath,
    'session.cookie.domain'   => null,
    'session.cookie.secure'   => !empty($CFG->cookiesecure),
    'session.cookie.lifetime' => 0,

    'session.phpsession.cookiename' => null,
    'session.phpsession.savepath'   => null,
    'session.phpsession.httponly'   => true,

    'enable.http_post' => false,

    'signature.algorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',

    'metadata.sign.enable'          => $catadminsaml->config->spmetadatasign ? true : false,
    'metadata.sign.certificate'     => $catadminsaml->certcrt,
    'metadata.sign.privatekey'      => $catadminsaml->certpem,
    'metadata.sign.privatekey_pass' => get_config('auth_catadmin', 'privatekeypass'),
    'metadata.sources'              => $metadatasources,

    'store.type' => !empty($CFG->auth_catadmin_store) ? $CFG->auth_catadmin_store : '\\auth_catadmin\\store',

    'proxy' => null,

    'authproc.sp' => array(
        50 => array(
            'class' => 'core:AttributeMap',
            'oid2name',
        ),
    )
);
