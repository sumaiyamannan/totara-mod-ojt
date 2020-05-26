<?php

/**
 * Local library
 *
 * @package    local
 * @subpackage hrimport
 * @copyright  &copy; 2016 Kineo Pacific {@link http://kineo.com.au}
 * @author     tri.le
 * @version    1.0
 */

namespace local_hrimport;

function get_field_mappings($element) {
    global $DB;

    if ($element->get_name()!='user') {
        $fields = array(
            'idnumber',
            'fullname',
            'shortname',
            'description',
            'frameworkidnumber',
            'parentidnumber',
            'typeidnumber',
            'timemodified'
        );
    } else {
        $fields = array(
            'idnumber',
            'timemodified',
            'username',
            'deleted',
            'firstname',
            'lastname',
            'firstnamephonetic',
            'lastnamephonetic',
            'middlename',
            'alternatename',
            'email',
            'emailstop',
            'city',
            'country',
            'timezone',
            'lang',
            'description',
            'url',
            'institution',
            'department',
            'phone1',
            'phone2',
            'address',
            'orgidnumber',
            'manageridnumber',
            'appraiseridnumber',
            'auth',
            'password',
            'suspended',
            'postitle',
            'posstartdate',
            'posenddate'
        );
        if (!totara_feature_disabled('positions')) {
            $fields[]= 'posidnumber';
        }
    }
    $config = get_config($element->get_source()->get_name());

    $cfields = $DB->get_records('org_type_info_field');
    $customfields = array();
    foreach ($cfields as $cf) {
        // TODO - Implement sync for file custom fields.
        if ($cf->datatype == 'file') {
            continue;
        }

        $customfields['customfield_'.$cf->shortname] = $cf->shortname;
    }

    $fieldmappings = array();
    foreach ($fields as $f) {
        if (empty($config->{'import_'.$f})) {
            continue;
        }
        if (empty($config->{'fieldmapping_'.$f})) {
            $fieldmappings[$f] = $f;
        } else {
            $fieldmappings[$config->{'fieldmapping_'.$f}] = $f;
        }
    }

    foreach (array_keys($customfields) as $f) {
        if (empty($config->{'import_'.$f})) {
            continue;
        }
        if (empty($config->{'fieldmapping_'.$f})) {
            $fieldmappings[$f] = $f;
        } else {
            $fieldmappings[$config->{'fieldmapping_'.$f}] = $f;
        }
    }

    return $fieldmappings;
}

function get_uploadedfile_path($element) {
    global $CFG;

    $name = $element->get_name();
    $fs = get_file_storage();
    $fieldid = get_config('totara_sync', "sync_{$name}_itemid");
    $fsfiles = $fs->get_area_files(SYSCONTEXTID, 'totara_sync', 'org', $fieldid, 'id DESC', false);
    $fsfile = reset($fsfiles);

    $temppath = $CFG->tempdir.'/totarasync/csv/org.csv';
    $fsfile->copy_content_to($temppath);
    return $temppath;
}

function export_xls($csvpath, $delimiter, $rowswitherrors, $fieldmapping) {
    global $CFG, $PAGE;
    require_once($CFG->libdir.'/tablelib.php');
    require_once($CFG->dirroot.'/local/hrimport/classes/excel_exporter.php');

    if (!$delimiter) {
        $delimiter = ',';
    }
    $csvfile = fopen($csvpath, 'r');
    $fields = fgetcsv($csvfile, 0, $delimiter);
    $fields[]= get_string('error');

    \Advanced_MoodleExcelWorksheet::set_background_color_function(function($row, $col) use(&$rowswitherrors, $fields, $fieldmapping) {
        if (!isset($rowswitherrors[$row-1])) {
            return;
        }
        $colabel = $fields[$col];
        if (empty($fieldmapping[$colabel])) {
            return;
        }
        $fieldname = $fieldmapping[$colabel];
        if (isset($rowswitherrors[$row-1][$fieldname])) {
            return 'FFC7CE';
        }
    });

    ob_start();
    $flextable = new \flexible_table('hr_import_validation');
    $flextable->define_baseurl($PAGE->url);
    $flextable->define_headers($fields);
    $flextable->define_columns($fields);
    // filename here is unimportant because what we return is the content of the file, not downloading immediately
    $flextable->is_downloading('excel2', 'filename');

    $flextable->setup();
    $num = 1;
    while (($rowdata = fgetcsv($csvfile, 0, $delimiter))) {
        if (isset($rowswitherrors[$num])) {
            $rowdata[]= implode(';', $rowswitherrors[$num]);
        }
        $flextable->add_data($rowdata);
        $num++;
    }
    $flextable->add_data(array_fill(0, count($fields), '-----'));
    $flextable->finish_output();
    $content = ob_get_clean();
    fclose($csvfile);
    header_remove();

    return $content;
}

function get_delimiter($elementname) {
    $delimiter = get_config("totara_sync_source_{$elementname}_csv", 'delimiter');
    return $delimiter ? $delimiter : ',';
}

function check_missing_column($delimiter, $fieldmapping, &$content) {
    $header = trim(strtok($content, "\n"));
    $headers = explode($delimiter, $header);

    $missing = array_diff(array_keys($fieldmapping), $headers);
    return $missing;
}
