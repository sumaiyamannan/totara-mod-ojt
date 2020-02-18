<?php
/**
 * Auto load libraries
 *
 * @package    auth_catadmin
 * @copyright  Alex Morris <alex.morris@catadmin.net.nz>
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../saml2/extlib/simplesamlphp/vendor/autoload.php');
require_once(__DIR__ . '/../saml2/extlib/xmlseclibs/xmlseclibs.php');

spl_autoload_register(
    function($classname) {
        $map = [
            'SAML2'      => 'saml2/src/',
            'Twig'       => 'twig/twig/lib/',
            'Psr'        => 'php-fig-log/',
            'SimpleSAML' => 'simplesamlphp/lib/',
            'sspmod'     => 'simplesamlphp/modules/',
        ];
        foreach ($map as $namespace => $subpath) {
            $classpath = explode('_', $classname);
            if ($classpath[0] != $namespace) {
                $classpath = explode('\\', $classname);
                if ($classpath[0] != $namespace) {
                    continue;
                }
            }

            $subpath = __DIR__ . '/../saml2/extlib/' . $subpath;
            if ($namespace == 'sspmod') {
                array_shift($classpath);
                $module = array_shift($classpath);
                $filepath = $subpath . "$module/lib/" . implode('/', $classpath) . '.php';
            } else {
                $filepath = $subpath . implode('/', $classpath) . '.php';
            }
            if (file_exists($filepath)) {
                require_once($filepath);
            }
        }
    }
);
