<?php

namespace auth_catadmin\admin;

use admin_setting_configtextarea;
use auth_saml2\idp_data;
use auth_saml2\idp_parser;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once("{$CFG->libdir}/adminlib.php");

/**
 * Validates & processes IdP metadata
 *
 * @package auth_catadmin\admin
 */
class setting_idpmetadata extends admin_setting_configtextarea {
    public function __construct() {
        // All parameters are hardcoded because there can be only one instance:
        // When it validates, it saves extra configs, preventing this component from being reused as is.
        parent::__construct(
            'auth_catadmin/idpmetadata',
            get_string('idpmetadata', 'auth_saml2'),
            get_string('idpmetadata_help', 'auth_saml2'),
            '',
            PARAM_RAW,
            80,
            5);
    }

    /**
     * Validate data before storage
     *
     * @param string $value
     * @return true|string Error message in case of error, true otherwise.
     * @throws \coding_exception
     */
    public function validate($value) {
        $value = trim($value);
        if (empty($value)) {
            return true;
        }

        try {
            $idps = $this->get_idps_data($value);
            $this->process_all_idps_metadata($idps);
        } catch (setting_idpmetadata_exception $exception) {
            error_log('auth_catadmin: ' . $exception->getMessage());
        }

        return true;
    }

    /**
     * @param idp_data[] $idps
     */
    private function process_all_idps_metadata($idps) {
        global $DB;

        $currentidpsrs = $DB->get_records('auth_catadmin_idps');
        $oldidps = array();
        foreach ($currentidpsrs as $idpentity) {
            if (!isset($oldidps[$idpentity->metadataurl])) {
                $oldidps[$idpentity->metadataurl] = array();
            }

            $oldidps[$idpentity->metadataurl][$idpentity->entityid] = $idpentity;
        }

        foreach ($idps as $idp) {
            $this->process_idp_metadata($idp, $oldidps);
        }
    }

    private function process_idp_metadata(idp_data $idp, &$oldidps) {
        $xpath = $this->get_idp_xml_path($idp);
        $idpelements = $this->find_all_idp_sso_descriptors($xpath);

        if ($idpelements->length == 1) {
            $this->process_idp_xml($idp, $idpelements->item(0), $xpath, $oldidps, 1);
        } else if ($idpelements->length > 1) {
            foreach ($idpelements as $childidpelements) {
                $this->process_idp_xml($idp, $childidpelements, $xpath, $oldidps, 0);
            }
        }

        $this->save_idp_metadata_xml($idp->idpurl, $idp->get_rawxml());
    }

    private function process_idp_xml(idp_data $idp, DOMElement $idpelements, DOMXPath $xpath,
                                     &$oldidps, $activedefault = 0) {
        global $DB;
        $entityid = $idpelements->getAttribute('entityID');

        // Locate a displayname element provided by the IdP XML metadata.
        $names = $xpath->query('.//mdui:DisplayName', $idpelements);
        $idpname = null;
        if ($names && $names->length > 0) {
            $idpname = $names->item(0)->textContent;
        } else if (!empty($idp->idpname)) {
            $idpname = $idp->idpname;
        } else {
            $idpname = get_string('idpnamedefault', 'auth_catadmin');
        }

        // Locate a logo element provided by the IdP XML metadata.
        $logos = $xpath->query('.//mdui:Logo', $idpelements);
        $logo = null;
        if ($logos && $logos->length > 0) {
            $logo = $logos->item(0)->textContent;
        }

        if (isset($oldidps[$idp->idpurl][$entityid])) {
            $oldidp = $oldidps[$idp->idpurl][$entityid];

            if (!empty($idpname) && $oldidp->defaultname !== $idpname) {
                $DB->set_field('auth_catadmin_idps', 'defaultname', $idpname, array('id' => $oldidp->id));
            }

            if (!empty($logo) && $oldidp->logo !== $logo) {
                $DB->set_field('auth_catadmin_idps', 'logo', $logo, array('id' => $oldidp->id));
            }

            // Remove the idp from the current array so that we don't delete it later.
            unset($oldidps[$idp->idpurl][$entityid]);
        } else {
            $newidp = new \stdClass();
            $newidp->metadataurl = $idp->idpurl;
            $newidp->entityid = $entityid;
            $newidp->activeidp = $activedefault;
            $newidp->defaultidp = 0;
            $newidp->defaultname = $idpname;

            $DB->insert_record('auth_catadmin_idps', $newidp);
        }
    }

    /**
     * @param $value
     * @return idp_data[]
     */
    public function get_idps_data($value) {
        $parser = new idp_parser();
        $idps = $parser->parse($value);

        // Download the XML if it was not parsed from the ipdmetadata field.
        foreach ($idps as $idp) {
            if (!is_null($idp->get_rawxml())) {
                continue;
            }

            $rawxml = @file_get_contents($idp->idpurl);
            if ($rawxml === false) {
                throw new setting_idpmetadata_exception(
                    get_string('idpmetadata_badurl', 'auth_catadmin', $idp->idpurl)
                );
            }
            $idp->set_rawxml($rawxml);
        }

        return $idps;
    }

    /**
     * @param idp_data $idp
     * @return
     */
    private function get_idp_xml_path(idp_data $idp) {
        $xml = new DOMDocument();
        if (!$xml->loadXML($idp->rawxml)) {
            throw new setting_idpmetadata_exception(get_string('idpmetadata_invalid', 'auth_catadmin'));
        }

        $xpath = new DOMXPath($xml);
        $xpath->registerNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
        $xpath->registerNamespace('mdui', 'urn:oasis:names:tc:SAML:metadata:ui');

        return $xpath;
    }

    /**
     * @param DOMXPath $xpath
     * @return DOMNodeList
     */
    private function find_all_idp_sso_descriptors(DOMXPath $xpath) {
        $idpelements = $xpath->query('//md:EntityDescriptor[//md:IDPSSODescriptor]');
        return $idpelements;
    }

    private function save_idp_metadata_xml($url, $xml) {
        global $CFG, $catadminsaml;
        require_once("{$CFG->dirroot}/auth/catadmin/setup.php");

        $file = $catadminsaml->get_file_idp_metadata_file($url);
        file_put_contents($file, $xml);
    }
}
