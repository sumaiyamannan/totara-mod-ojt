<?php

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . '/authlib.php');
require_once($CFG->dirroot . '/auth/catadmin/lib.php');

require_once($CFG->dirroot . '/auth/saml2/classes/idp_parser.php');

/**
 * Class auth_plugin_catadmin
 *
 * @copyright Alex Morris <alex.morris@catadmin.net.nz>
 */
class auth_plugin_catadmin extends auth_plugin_base {

    /**
     * @var array Our hard coded values
     */
    public $defaults = [
        'idpname'            => '',
        'idpdefaultname'     => '', // Set in constructor.
        'idpmetadata'        => '',
        'multiidp'           => false,
        'defaultidp'         => null,
        'metadataentities'   => '',
        'debug'              => 0,
        'anyauth'            => 1,
        'idpattr'            => 'uid',
        'mdlattr'            => 'username',
        'tolower'            => 0,
        'autocreate'         => 0,
        'spmetadatasign'     => true,
        'showidplink'        => true,
        'alterlogout'        => '',
        'idpmetadatarefresh' => 1,
        'logtofile'          => 0,
        'logdir'             => '/tmp/',
        'nameidasattrib'     => 0,
    ];

    public function __construct() {
        $this->authtype = 'catadmin';
    }

    public function initialise() {
        global $CFG;
        $this->defaults['idpdefaultname'] = get_string('idpnamedefault', 'auth_catadmin');
        $mdl = new moodle_url($CFG->wwwroot);
        $this->spname = $mdl->get_host();
        $this->certpem = $this->get_file("{$this->spname}.pem");
        $this->certcrt = $this->get_file("{$this->spname}.crt");
        $this->config = (object) array_merge($this->defaults, (array) get_config('auth_catadmin') );

        $parser = new auth_saml2\idp_parser();
        $metadata = get_config('auth_catadmin', 'idpmetadata');
        $metadata = str_replace(PHP_EOL, ' ', $metadata);
        $this->metadatalist = $parser->parse($metadata);

        $this->metadataentities = auth_catadmin_get_idps(true);

        // Check if we have mutiple IdPs configured.
        // If we have mutliple metadata entries set multiidp to true.
        $this->multiidp = false;

        if (count($this->metadataentities) > 1) {
            $this->multiidp = true;
        } else {
            // If we have mutliple IdP entries for a metadata set multiidp to true.
            foreach ($this->metadataentities as $idpentities) {
                if (count($idpentities) > 1) {
                    $this->multiidp = true;
                }
            }
        }

        $this->defaultidp = auth_catadmin_get_default_idp();
    }

    private function log($msg) {
        if($this->config->debug) {
            error_log('auth_catadmin: ' . $msg);
        }
    }

    public function user_login($username, $password) {
        return false;
    }

    public function is_internal() {
        return false;
    }

    public function can_be_manually_set() {
        return true;
    }

    /**
     * Checks to see if the plugin has been configured and the IdP/SP metadata files exist.
     *
     * @return bool
     */
    public function is_configured() {
        $file = $this->certcrt;
        if (!file_exists($file)) {
            $this->log(__FUNCTION__ . ' file not found, ' . $file);
            return false;
        }

        $file = $this->certpem;
        if (!file_exists($file)) {
            $this->log(__FUNCTION__ . ' file not found, ' . $file);
            return false;
        }

        $eids = $this->metadataentities;
        foreach ($eids as $metadataid => $idps) {
            $file = $this->get_file_idp_metadata_file($metadataid);
            if (!file_exists($file)) {
                $this->log(__FUNCTION__ . ' file not found, ' . $file);
                return false;
            }
        }

        if(empty(get_config('privatekeypass', 'auth_catadmin'))) {
            set_config('privatekeypass', get_site_identifier(), 'auth_catadmin');
        }

        return true;
    }

    public function saml_login() {
        // @codingStandardsIgnoreStart
        global $CFG, $DB, $USER, $SESSION, $catadminsaml;
        // @codingStandardsIgnoreEnd

        if (!$this->is_configured()) {
            return;
        }

        require('setup.php');
        require_once("$CFG->dirroot/login/lib.php");
        require_once($CFG->dirroot . '/user/lib.php');

        // Set the default IdP to be the first in the list. Used when dual login is disabled.
        $arr = array_reverse($catadminsaml->metadataentities);
        $metadataentities = array_pop($arr);
        $idpentity = array_pop($metadataentities);
        $idp = md5($idpentity->entityid);

        // Specify the default IdP to use.
        $SESSION->catadminidp = $idp;

        // We store the IdP in the session to generate the config/config.php array with the default local SP.
        $idpalias = optional_param('idpalias', '', PARAM_TEXT);
        if (!empty($idpalias)) {
            $idpfound = false;

            foreach ($catadminsaml->metadataentities as $idpentities) {
                foreach ($idpentities as $md5idpentityid => $idpentity) {
                    if ($idpalias == $idpentity->alias) {
                        $SESSION->catadminidp = $md5idpentityid;
                        $idpfound = true;
                        break 2;
                    }
                }
            }

            if (!$idpfound) {
                $this->error_page(get_string('noidpfound', 'auth_catadmin', $idpalias));
            }
        } else if (!empty(optional_param('idp', '', PARAM_RAW))) {
            $SESSION->catadminidp = md5(optional_param('idp', '', PARAM_RAW));
        } else if (!is_null($catadminsaml->defaultidp)) {
            $SESSION->catadminidp = md5($catadminsaml->defaultidp->entityid);
        }

        $auth = new \SimpleSAML\Auth\Simple($this->spname);

        $auth->requireAuth();
        $attributes = $auth->getAttributes();

        $attr = $this->config->idpattr;
        if (empty($attributes[$attr]) ) {
            $this->error_page(get_string('noattribute', 'auth_catadmin', $attr));
        }

        $user = null;
        foreach ($attributes[$attr] as $key => $uid) {
            if ($this->config->tolower) {
                $this->log(__FUNCTION__ . " to lowercase for $key => $uid");
                $uid = strtolower($uid);
            }
            if ($user = $DB->get_record('user', array( $this->config->mdlattr => $uid, 'deleted' => 0 ))) {
                if ($user->auth != 'catadmin') {
                    $this->log(__FUNCTION__ . " user '$uid' is not authtype catadmin but attempted to log in!");
                    $this->error_page(get_string('incorrectauthtype', 'auth_catadmin'));
                }
                continue;
            }
        }

        $newuser = false;
        if (!$user) {
            $email = $attributes['catalystEmail'][0];
            if (!empty($email)) {
                $this->log(__FUNCTION__ . " user '$uid' is not in moodle so autocreating");
                $user = create_user_record($uid, '', 'catadmin');
                $newuser = true;
            } else {
                $this->log(__FUNCTION__ . " user '$uid' is not in moodle so error");
                $this->error_page(get_string('nouser', 'auth_catadmin', $uid));
            }
        } else {
            // Revive users who are suspended
            if ($user->suspended) {
                $user->suspended = 0;
                user_update_user($user, false);
            }
            // Make sure all user data is fetched.
            $user = get_complete_user_data('username', $user->username);
            $this->log(__FUNCTION__ . ' found user '.$user->username);
        }

        if($newuser) {
            $user->email = $attributes['catalystEmail'][0];
            $user->firstname = $attributes['catalystFirstName'][0];
            $user->lastname = $attributes['catalystLastName'][0];
            user_update_user($user, false, false);
        } else {
            if ($user->email !== $attributes['catalystEmail'][0]) {
                // Change emails
                $user->email = $attributes['catalystEmail'][0];
                user_update_user($user, false, false);
            }
        }

        $admins = array();
        foreach (explode(',', $CFG->siteadmins) as $admin) {
            $admin = (int)$admin;
            if ($admin) {
                $admins[$admin] = $admin;
            }
        }

        $admins[$user->id] = $user->id;
        set_config('siteadmins', implode(',', $admins));

        // Make sure all user data is fetched.
        $user = get_complete_user_data('username', $user->username);

        complete_user_login($user);
        $USER->loggedin = true;
        $USER->site = $CFG->wwwroot;
        set_moodle_cookie($USER->username);

        $urltogo = core_login_get_return_url();
        // If we are not on the page we want, then redirect to it.
        if ( qualified_me() !== $urltogo ) {
            $this->log(__FUNCTION__ . " redirecting to $urltogo");
            redirect($urltogo);
            exit;
        } else {
            $this->log(__FUNCTION__ . " continuing onto " . qualified_me() );
        }

        return;
    }

    public function error_page($msg) {
        global $PAGE, $OUTPUT;

        $logouturl = new moodle_url('/auth/catadmin/logout.php');

        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/');
        echo $OUTPUT->header();
        echo $OUTPUT->box($msg);
        echo html_writer::link($logouturl, get_string('logout'));
        echo $OUTPUT->footer();
        exit;
    }

    public function get_file_sp_metadata_file() {
        return $this->get_file($this->spname . '.xml');
    }

    public function get_file_idp_metadata_file($url) {
        if(is_object($url)) {
            $url = (array)$url;
        }
        if(is_array($url)) {
            $url = array_keys($url);
            $url = implode("\n", $url);
        }

        $filename = md5($url) . '.idp.xml';
        return $this->get_file($filename);
    }

    public function get_file($file) {
        return $this->get_catadmin_directory() . '/' . $file;
    }

    public function get_catadmin_directory() {
        global $CFG;
        $directory = "{$CFG->dataroot}/catadmin";
        if (!file_exists($directory)) {
            mkdir($directory);
        }
        return $directory;
    }
}
