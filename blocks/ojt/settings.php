<?php

/**
 * Comment
 *
 * @package    package
 * @subpackage sub_package
 * @copyright  &copy; 2017 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

defined('MOODLE_INTERNAL') || die();

if($ADMIN->fulltree) {
    // roles for which to display the ojt activites in the block
    $settings->add(new admin_setting_configtext('block_ojt/displayforroles', new lang_string('displayforroles', 'block_ojt'),
        new lang_string('displayforrolesdesc', 'block_ojt'), '', PARAM_TEXT));
}

