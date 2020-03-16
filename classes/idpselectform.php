<?php

namespace auth_catadmin;

defined('MOODLE_INTERNAL') || die();

use moodleform;

require_once("$CFG->libdir/formslib.php");

/**
 * Login form showing selection of the IdP's.
 *
 * @package auth_catadmin
 */
class idpselectform extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $idpentityids = explode(PHP_EOL, get_config('auth_catadmin', 'idpmetadata'));

        $selectvalues = [];

	    $rdidp = "";
        foreach ($idpentityids as $idpentity) {
            if (is_string($idpentity)) {
		        $selectvalues[$idpentity] = $idpentity;
		        if (date('z') % 2 == 0) {
			        $rdidp = $idpentity;
		        }
            } else {
                foreach ((array)$idpentity as $subidpentity => $active) {
		            if ($active) {
			            if (date('z')  % 2 == 0) {
			                $rdidp = $idpentity;
			            }
			            $selectvalues[$subidpentity] = $subidpentity;
                    }
                }
            }
        }

        $select = $mform->addElement('select', 'idp', get_string('select_idp_button', 'auth_catadmin'), $selectvalues);
        $select->setSelected($rdidp);

        $mform->addElement('submit', 'login', get_string('select_idp_button', 'auth_catadmin'));
    }
}
