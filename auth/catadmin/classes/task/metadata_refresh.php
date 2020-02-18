<?php

namespace auth_catadmin\task;

use auth_catadmin\admin\setting_idpmetadata;
use auth_saml2\idp_parser;
use moodle_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Refresh metadata from IdP's
 *
 * @package auth_catadmin
 */
class metadata_refresh extends \core\task\scheduled_task {

    /**
     * @var idp_parser
     */
    private $idpparser;

    public function get_name() {
        return get_string('taskmetadatarefresh', 'auth_catadmin');
    }

    public function execute($force = false) {
        foreach (explode(PHP_EOL, get_config('auth_catadmin', 'idpmetadata')) as $idpmetadata) {
            if (empty($idpmetadata)) {
                mtrace('IdP metadata not configured.');
                return false;
            }

            if (!$this->idpparser instanceof idp_parser) {
                $this->idpparser = new idp_parser();
            }

            if ($this->idpparser->check_xml($idpmetadata) == true) {
                mtrace('IdP metadata config not a URL, nothing to refresh.');
                return false;
            }

            $idpmetadata_setting = new setting_idpmetadata();
            $idpmetadata_setting->validate($idpmetadata);

            mtrace('IdP metadata refresh completed successfully.');
        }
        return true;
    }
}
