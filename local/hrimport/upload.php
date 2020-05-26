<?php

/**
 * Based on admin/tool/totara_sync/admin/uploadsourcefile.php
 *
 * @package    local
 * @subpackage hrimport
 * @copyright  &copy; 2017 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */

namespace local_hrimport;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(__DIR__.'/classes/forms.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/local/hrimport/locallib.php');

global $OUTPUT;

require_login(null, true);
// WR#333652: Restrict HR Import access to administrators.
require_capability('moodle/site:config', context_system::instance());

$context = \context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url('/local/hrimport/index.php');
$PAGE->set_title(get_string('hrimport','local_hrimport'));
$PAGE->set_heading(get_string('upload_csv','local_hrimport'));

\core_php_time_limit::raise(0);
raise_memory_limit(MEMORY_HUGE);

// Process actions
$elements = totara_sync_get_elements($onlyenabled=true);

$mform = new csv_upload();

if (($data = $mform->get_data())) {
    require_once($CFG->dirroot.'/local/hrimport/validationlib.php');
    $basetmpdir = $CFG->tempdir.'/hrimport';
    if (!is_dir($basetmpdir)) {
        mkdir($basetmpdir);
    }

    $fs = get_file_storage();
    $readyfiles = array();

    $zip = new \ZipArchive();
    $zippath = $basetmpdir.'/HR-Import-Validation-Error.zip';
    $zip->open($zippath, \ZipArchive::CREATE);
    $errorfiles = array();
    $correctfiles = array();

    foreach ($elements as $e) {
        $elementname = $e->get_name();

        $fs->delete_area_files($context->id, 'totara_sync', $elementname);

        //save draftfile to file directory
        if (empty($data->$elementname) || !$mform->get_new_filename($elementname)) {
            continue;
        }

        $fieldmapping = get_field_mappings($e);
        $delimiter = get_delimiter($elementname);

        $uploadcontent = $mform->get_file_content($elementname);
        if (($missingcols = check_missing_column($delimiter, $fieldmapping, $uploadcontent))) {
            $mform->setElementError($elementname, get_string('missingrequiredcolumn', 'local_hrimport', implode(', ', $missingcols)));
            continue;
        }

        $draftid = $data->$elementname;
        $filerecord = array(
            'contextid' => $context->id,
            'component' => 'totara_sync',
            'filearea'  => $elementname,
            'itemid'    => $draftid,
            'filepath'  => '/',
            'filename'  => $mform->get_new_filename($elementname),
        );
        $uploadedfile = $fs->create_file_from_string($filerecord, $uploadcontent);
        unset($uploadcontent);
        set_config("sync_{$elementname}_itemid", $draftid, 'totara_sync');

        $functionname = "\\local_hrimport\\validate_{$elementname}_element";
        if (function_exists($functionname)) {
            $rowswitherrors = $functionname($e);
            if ($rowswitherrors) {
                $csvpath = $basetmpdir."/$elementname";
                $uploadedfile->copy_content_to($csvpath);
                $errorfile = export_xls($csvpath, $delimiter, $rowswitherrors, $fieldmapping);
                $zip->addFromString($elementname.'.xlsx', $errorfile);
                unlink($csvpath);
                $errorfiles[$elementname] = true;
            } else {
                $correctfiles[$elementname] = true;
            }
        }
    }
    $zip->close();

    if ($errorfiles) {
        if (count($errorfiles) > 1) {
            $filetodownload = basename($zippath);
        } else {
            $filetodownload = 'HR-Import-'.basename($csvpath).'-Validation-Error.xlsx';
            $errorfilepath = $basetmpdir.'/'.$filetodownload;
            file_put_contents($errorfilepath, $errorfile);
        }
    }
}

if (!empty($filetodownload)) {
    $url = new \moodle_url('/local/hrimport/download.php', array('filename' => $filetodownload));
    header('Refresh: 2; '.($url->out(false)));
}

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('uploadsyncfiles', 'tool_totara_sync'));

if (!empty($correctfiles)) {
    foreach (array_keys($correctfiles) as $filename) {
        echo $OUTPUT->notification(get_string('correctfile', 'local_hrimport', ucfirst($filename)), 'notifysuccess');
    }
}

$mform->display();

echo $OUTPUT->footer();
