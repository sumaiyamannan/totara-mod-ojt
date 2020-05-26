<?php

/**
 * Local library
 *
 * @package    local
 * @subpackage hrimport
 * @copyright  &copy; 2017 CG Kineo {@link http://www.kineo.com}
 * @author     kaushtuv.gurung
 * @version    1.0
 */
namespace local_hrimport;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/admin/tool/totara_sync/lib.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/org.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/pos.php');
require_once($CFG->dirroot.'/admin/tool/totara_sync/elements/user.php');

/**
 * Validating the organisation elements
 */
function validate_org_element($element) {
    global $CFG, $DB;

    if (!empty($CFG->debugdisplay)) {
        ini_set('display_errors', false);
        ini_set('log_errors', true);
    }
    $synctable = $element->get_source_sync_table();
    $synctable_clone = $element->get_source_sync_table_clone($synctable);
    if (!empty($CFG->debugdisplay)) {
        ini_set('display_errors', true);
        ini_set('log_errors', false);
    }

    $elname = $element->get_name();

    $rowerrors = array();

    /// Check frameworks
    $sql = "SELECT id, frameworkidnumber
              FROM {{$synctable}} sync
             WHERE frameworkidnumber NOT IN
                (SELECT idnumber
                   FROM {{$elname}_framework}
                )";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id]['frameworkidnumber'] = get_string('frameworkxnotexist', 'tool_totara_sync', $r->frameworkidnumber);
    }
    $rs->close();

    /// Check duplicate idnumbers and warn
    $sql = " SELECT sync.id, sync.idnumber
               FROM {{$synctable}} sync
               JOIN (
                SELECT idnumber
                  FROM {{$synctable_clone}}
              GROUP BY idnumber
                HAVING COUNT(*) > 1) duplicate
               ON (sync.idnumber=duplicate.idnumber)";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id]['idnumber'] = get_string('duplicateidnumberx', 'tool_totara_sync', $r->idnumber);
    }
    $rs->close();

    /// Check parents
    $sql = "SELECT sync.id, sync.parentidnumber
              FROM {{$synctable}} sync
             WHERE sync.parentidnumber IS NOT NULL
               AND sync.parentidnumber != ''
               AND sync.parentidnumber != '0'
               AND sync.parentidnumber NOT IN (SELECT idnumber FROM {{$synctable_clone}})";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id]['parentidnumber'] = get_string('parentxnotexistinfile', 'tool_totara_sync', $r->parentidnumber);
    }
    $rs->close();

    /// Check types
    $sql = "SELECT sync.id, sync.typeidnumber
             FROM {{$synctable}} sync
            WHERE sync.typeidnumber IS NOT NULL
              AND sync.typeidnumber != ''
              AND sync.typeidnumber != '0'
              AND sync.typeidnumber NOT IN (SELECT idnumber FROM {{$elname}_type})";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id]['typeidnumber'] = get_string('typexnotexist', 'tool_totara_sync', $r->typeidnumber);
    }
    $rs->close();

    /// Check circular parent references
    /// A circular reference will never have a root node (parentid == NULL)
    /// We can determine CRs by eliminating the nodes of the valid trees
    $sql = "SELECT idnumber, MAX(parentidnumber)
              FROM {{$synctable}}
          GROUP BY idnumber";
    $nodes = $DB->get_records_sql_menu($sql);

    // Start eliminating nodes from the valid trees
    // Start at the top so get all the root nodes (no parentid)
    $top_nodes_1 = array_keys($nodes, '');
    $top_nodes_2 = array_keys($nodes, '0');

    // Merge top level nodes into one array
    $goodnodes = array_merge($top_nodes_1, $top_nodes_2);

    while (!empty($goodnodes)) {
        $newgoodnodes = array();
        foreach ($goodnodes as $nid) {
            // Unset good parentnodes
            unset($nodes[$nid]);

            // Get all good childnodes
            $newgoodnodes = array_merge($newgoodnodes, array_keys($nodes, $nid));
        }

        $goodnodes = $newgoodnodes;
    }

    // Remaining nodes mean we have circular refs!
    if (!empty($nodes)) {
        list($insql, $inparams) = $DB->get_in_or_equal(array_keys($nodes));
        $circularnodes = $DB->get_records_select($synctable, "idnumber $insql", $inparams);
        foreach ($circularnodes as $n) {
            $rowerrors[$n->id]['parentidnumber'] = get_string('circularreferror', 'tool_totara_sync', array('naughtynodes' => $n->idnumber));
        }
    }

    /// Get all hierarchy records to be created/updated - exclude obsolete/unmodified items
    $sql = "SELECT sync.*
              FROM {{$synctable}} sync
             WHERE sync.idnumber NOT IN
                (SELECT ii.idnumber
                   FROM {{$elname}} ii
              LEFT JOIN {{$synctable_clone}} ss
                     ON (ii.idnumber = ss.idnumber)
                  WHERE ss.idnumber IS NULL
                     OR ss.timemodified = ii.timemodified)";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        /// Check custom fields
        if (($customfielddata = json_decode($r->customfields, true))) {
            $customfielddata = array_map('trim', $customfielddata);
            $customfields = array_keys($customfielddata);
            if (empty($r->typeidnumber)) {
                $customfielddata = array_filter($customfielddata);
                if (empty($customfielddata)) {
                    // Type and customfield data empty, so skip ;)
                    continue;
                }
                $rowerrors[$r->id]['typeidnumber'] = get_string('customfieldsnotype', 'tool_totara_sync', "({$elname}:{$r->idnumber})");
            }
            if (!($typeid = $DB->get_field($elname.'_type', 'id', array('idnumber' => $r->typeidnumber)))) {
                $rowerrors[$r->id]['typeidnumber'] = get_string('typexnotfound', 'tool_totara_sync', $r->typeidnumber);
            }
            foreach ($customfields as $c) {
                if (empty($customfielddata[$c])) {
                    // Don't check empty fields, as this might be another type's custom field
                    continue;
                }
                $shortname = str_replace('customfield_', '', $c);
                if (!$DB->record_exists($elname.'_type_info_field', array('typeid' => $typeid, 'shortname' => $shortname))) {
                    $rowerrors[$r->id][$c] = get_string('customfieldnotexist', 'tool_totara_sync', array('shortname' => $shortname, 'typeidnumber' => $r->typeidnumber));
                }
            }
        }
    }
    $rs->close();
    return $rowerrors;
}

function validate_pos_element($element) {
    return validate_org_element($element);
}

function validate_user_element($element) {
    global $CFG, $DB;

    if (!empty($CFG->debugdisplay)) {
        ini_set('display_errors', false);
        ini_set('log_errors', true);
    }
    $synctable = $element->get_source_sync_table();
    $synctable_clone = $element->get_source_sync_table_clone($synctable);
    if (!empty($CFG->debugdisplay)) {
        ini_set('display_errors', true);
        ini_set('log_errors', false);
    }

    $config = get_config('totara_sync_element_user');

    $rowerrors = array();

    if (!$syncfields = $DB->get_record_sql("SELECT * FROM {{$synctable}}", null, IGNORE_MULTIPLE)) {
        return array(); // Nothing to check.
    }
    $issane = array();

    // Get duplicated idnumbers.
    $badids = get_duplicated_values($synctable, $synctable_clone, 'idnumber', 'duplicateuserswithidnumberx');
    $rowerrors = merge_error_array($rowerrors, $badids);

    $badids = check_empty_values($synctable, 'idnumber', 'emptyvalueidnumberx');
    $rowerrors = merge_error_array($rowerrors, $badids);

    $badids = get_duplicated_values($synctable, $synctable_clone, 'username', 'duplicateuserswithusernamex');
    $rowerrors = merge_error_array($rowerrors, $badids);

    $badids = check_empty_values($synctable, 'username', 'emptyvalueusernamex');
    $rowerrors = merge_error_array($rowerrors, $badids);

    $badids = check_values_in_db($synctable, 'username', 'duplicateusernamexdb');
    $rowerrors = merge_error_array($rowerrors, $badids);

    $badids = check_invalid_username($synctable);
    $rowerrors = merge_error_array($rowerrors, $badids);

    // Get empty firstnames. If it is provided then it must have a non-empty value.
    if (isset($syncfields->firstname)) {
        $badids = check_empty_values($synctable, 'firstname', 'emptyvaluefirstnamex');
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    // Get empty lastnames. If it is provided then it must have a non-empty value.
    if (isset($syncfields->lastname)) {
        $badids = check_empty_values($synctable, 'lastname', 'emptyvaluelastnamex');
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    // Check position start date is not larger than position end date.
    if (isset($syncfields->posstartdate) && isset($syncfields->posenddate)) {
        $badids = get_invalid_start_end_dates($synctable, 'posstartdate', 'posenddate', 'posstartdateafterenddate');
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    // Check invalid language set.
    if (isset($syncfields->lang)) {
        $badids = get_invalid_lang($synctable);
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    if (empty($config->allow_create)) {
        $badids = check_users_unable_to_revive($synctable);
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    if (!isset($config->allowduplicatedemails)) {
        $config->allowduplicatedemails = 0;
    }
    if (!isset($config->ignoreexistingpass)) {
        $config->ignoreexistingpass = 0;
    }
    if (isset($syncfields->email) && !$config->allowduplicatedemails) {
        // Get duplicated emails.
        $badids = get_duplicated_values($synctable, $synctable_clone, 'email', 'duplicateuserswithemailx');
        $rowerrors = merge_error_array($rowerrors, $badids);
        // Get empty emails.
        $badids = check_empty_values($synctable, 'email', 'emptyvalueemailx');
        $rowerrors = merge_error_array($rowerrors, $badids);
        // Check emails against the DB to avoid saving repeated values.
        $badids = check_values_in_db($synctable, 'email', 'duplicateusersemailxdb');
        $rowerrors = merge_error_array($rowerrors, $badids);
        // Get invalid emails.
        $badids = get_invalid_emails($synctable);
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    // Get invalid options (in case of menu of choices).
    if ($syncfields->customfields != '[]') {
        $badids = validate_custom_fields($synctable);
        $rowerrors = merge_error_array($rowerrors, $badids);
    }

    // The idea of this loop is to make sure that all users in the synctable are valid regardless of the order they are created.
    // Example: user1 is valid but his manager is not and his manager is checked later, so user1 will be marked as valid when he is not.
    // This loop avoids that behaviour by checking in each iteration if there are still invalid users.
    $rowerrors1 = $rowerrors;
    $dbman = $DB->get_manager();
    while (1) {
        // Get invalid positions.
        if (isset($syncfields->posidnumber)) {
            $badids = get_invalid_org_pos($synctable, 'pos', 'posidnumber', 'posxnotexist');
            // we also need to check if the pos exist in the pos CSV uploaded along with this user CSV
            if ($dbman->table_exists('totara_sync_pos')) {
                $badids2 = get_invalid_org_pos($synctable, 'totara_sync_pos', 'posidnumber', 'posxnotexist');
                $badids = array_intersect_key($badids, $badids2);
            }
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
        }

        // Get invalid orgs.
        if (isset($syncfields->orgidnumber)) {
            $badids = get_invalid_org_pos($synctable, 'org', 'orgidnumber', 'orgxnotexist');
            // we also need to check if the org exist in the org CSV uploaded along with this user CSV
            if ($dbman->table_exists('totara_sync_org')) {
                $badids2 = get_invalid_org_pos($synctable, 'totara_sync_org', 'orgidnumber', 'orgxnotexist');
                $badids = array_intersect_key($badids, $badids2);
            }
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
        }

        // Get invalid managers and self-assigned users.
        if (isset($syncfields->manageridnumber)) {
            $badids = get_invalid_roles($synctable, $synctable_clone, 'manager');
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
            $badids = check_self_assignment($synctable, 'manageridnumber', 'selfassignedmanagerx');
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
        }

        // Get invalid appraisers and self-assigned users.
        if (isset($syncfields->appraiseridnumber)) {
            $badids = get_invalid_roles($synctable, $synctable_clone, 'appraiser');
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
            $badids = check_self_assignment($synctable, 'appraiseridnumber', 'selfassignedappraiserx');
            $rowerrors1 = merge_error_array($rowerrors1, $badids);
        }

        $rowerrors = merge_error_array($rowerrors, $rowerrors1);
        if ($rowerrors1) {
            // Split $invalidids array into chunks as there are varying limits on the amount of parameters.
            $invalidids_multi = array_chunk(array_keys($rowerrors1), $DB->get_max_in_params());
            foreach ($invalidids_multi as $invalidids) {
                list($badids, $params) = $DB->get_in_or_equal($invalidids);
                $DB->delete_records_select($synctable, "id $badids", $params);
                $DB->delete_records_select($synctable_clone, "id $badids", $params);
            }
            unset($invalidids_multi);
            $rowerrors1 = array();
        } else {
            break;
        }
    }

    return $rowerrors;
}

function get_duplicated_values($synctable, $synctable_clone, $field, $identifier) {
    global $DB;

    $errorrows = array();
    $params = array();
    $extracondition = '';
    if (!is_source_all_records()) {
        $extracondition = "WHERE deleted = ?";
        $params[0] = 0;
    }
    $sql = "SELECT id, idnumber, $field
              FROM {{$synctable}}
             WHERE $field IN (SELECT $field FROM {{$synctable_clone}} $extracondition GROUP BY $field HAVING count($field) > 1)";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $errorrows[$r->id][$field]= get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $errorrows;
}

function check_empty_values($synctable, $field, $identifier) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $sql = "SELECT id, idnumber
              FROM {{$synctable}}
             WHERE $field = ''";
    if (!is_source_all_records() && $field != 'idnumber') {
        $sql .= ' AND deleted = ?'; // Avoid users that will be deleted.
        $params[0] = 0;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$field] = get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function check_values_in_db($synctable, $field, $identifier) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $sql = "SELECT s.id, s.idnumber, s.$field
              FROM {{$synctable}} s
        INNER JOIN {user} u ON s.idnumber <> u.idnumber
               AND s.$field = u.$field";
    if (!is_source_all_records()) {
        $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
        $params[0] = 0;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$field]= get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function check_invalid_username($synctable) {
    global $DB;

    $rowerrors = array();

    // Get a list of all the usernames.
    $sql = "SELECT id, idnumber, username FROM {{$synctable}}";
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        // Get a clean version of the username with all invalid characters removed.
        $clean_username = clean_param($r->username, PARAM_USERNAME);

        // The cleaned username doesn't match the original. There's a issue.
        if ($r->username !== $clean_username) {
            // Check if the username is mixed case, if it is that is fine, it will be converted to lower case later.
            // The conversion is done in {@see \totara_sync_element_user::create_user()}
            if (\core_text::strtolower($r->username) !== $clean_username) {
                // The cleaned username is not just a lowercase version of the original,
                // characters have been removed, so log an error and record the id.
                $rowerrors[$r->id]['username'] = get_string('invalidusernamex', 'tool_totara_sync', $r);
            } else {
                // The cleaned username has only had uppercase characters changed to lower case.
                // It's acceptable so just flag a warning. the username will be imported in lowercase.
                $rowerrors[$r->id]['username'] = get_string('invalidcaseusernamex', 'tool_totara_sync', $r);
            }
        }
    }
    $rs->close();

    return $rowerrors;
}

function get_invalid_start_end_dates($synctable, $datefield1, $datefield2, $identifier) {
    global $DB;

    $rowerrors = array();
    $sql = "SELECT s.id, s.idnumber
            FROM {{$synctable}} s
            WHERE s.$datefield1 > s.$datefield2
            AND s.$datefield2 != 0";
    if (!is_source_all_records()) {
        $sql .= ' AND s.deleted = 0'; // Avoid users that will be deleted.
    }
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$datefield1] = get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function get_invalid_lang($synctable) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $extracondition = '';
    if (!is_source_all_records()) {
        $extracondition = "AND deleted = ?";
        $params[0] = 0;
    }
    $sql = "SELECT id, idnumber, lang
              FROM {{$synctable}}
            WHERE lang != '' AND lang IS NOT NULL {$extracondition}";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        if (!get_string_manager()->translation_exists($r->lang)) {
            // Add log entry for invalid language but don't skip user.
            $rowerrors[$r->id]['lang'] = get_string('invalidlangx', 'tool_totara_sync', $r);
        }
        unset($r);
    }
    $rs->close();
    return $rowerrors;
}

function check_users_unable_to_revive($synctable) {
    global $DB;

    $rowerrors = array();
    $sql = "SELECT s.id, s.idnumber
              FROM {{$synctable}} s
              INNER JOIN {user} u ON s.idnumber = u.idnumber
             WHERE u.deleted = 1";
    if (!is_source_all_records()) {
        // With sourceallrecords on we also need to check the deleted column in the sync table.
        $sql .= ' AND s.deleted = 0';
    }
    $rs = $DB->get_recordset_sql($sql);
    foreach ($rs as $r) {
        $rowerrors[$r->id]['idnumber'] = get_string('cannotupdatedeleteduserx', 'tool_totara_sync', $r->idnumber);
    }
    $rs->close();

    return $rowerrors;
}

function get_invalid_emails($synctable) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $extracondition = '';
    if (!is_source_all_records()) {
        $extracondition = "AND deleted = ?";
        $params[0] = 0;
    }
    $sql = "SELECT id, idnumber, email
              FROM {{$synctable}}
             WHERE email IS NOT NULL {$extracondition}";
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        if (!validate_email($r->email)) {
            $rowerrors[$r->id]['email'] = get_string('invalidemailx', 'tool_totara_sync', $r);
        }
    }
    $rs->close();

    return $rowerrors;
}

function validate_custom_fields($synctable) {
    global $DB;

    $customfieldsdb = get_customfieldsdb();
    $params = !is_source_all_records() ? array('deleted' => 0) : array();
    $rowerrors = array();
    $rs = $DB->get_recordset($synctable, $params, '', 'id, idnumber, customfields');

    // Used to force a warning on the sync completion message without skipping users.
    $forcewarning = false;

    // Keep track of the fields that need to be tested for having unique values.
    $unique_fields = array ();

    foreach ($rs as $r) {
        $customfields = json_decode($r->customfields, true);
        if (!empty($customfields)) {
            foreach ($customfields as $name => $value) {
                // Check each of the fields that have attributes that may affect
                // whether the sync data will be accepted or not.
                if ($customfieldsdb[$name]['required'] && trim($value) == '' && empty($customfieldsdb[$name]['default'])) {
                    $rowerrors[$r->id][$name] = get_string('fieldrequired', 'tool_totara_sync', array('idnumber' => $r->idnumber, 'fieldname' => $name));
                }

                if (isset($customfieldsdb[$name]['menu_options'])) {
                    if (trim($value) != '' && !in_array(core_text::strtolower($value), $customfieldsdb[$name]['menu_options'])) {
                        // Check menu value matches one of the available options, add an warning to the log if not.
                        $rowerrors[$r->id][$name] = get_string('optionxnotexist', 'tool_totara_sync', array('idnumber' => $r->idnumber, 'option' => $value, 'fieldname' => $name));
                    }
                } else if ($customfieldsdb[$name]['forceunique']) {
                    // Note: Skipping this for menu custom fields as the UI does not enforce uniqueness for them.

                    $sql = "SELECT uid.data
                              FROM {user} usr
                              JOIN {user_info_data} uid ON usr.id = uid.userid
                             WHERE usr.idnumber != :idnumber
                               AND uid.fieldid = :fieldid
                               AND uid.data = :data";
                    // Check that the sync value does not exist in the user info data.
                    $params = array ('idnumber' => $r->idnumber, 'fieldid' => $customfieldsdb[$name]['id'], 'data' => $value);
                    $cfdata = $DB->get_records_sql($sql, $params);
                    // If the value already exists in the database then flag an error. If not, record
                    // it in unique_fields to later verify that it's not duplicated in the sync data.
                    if ($cfdata) {
                        $rowerrors[$r->id][$name] = get_string('fieldduplicated', 'tool_totara_sync', (object)array('idnumber' => $r->idnumber, 'fieldname' => $name, 'value' => $value));
                    } else {
                        $unique_fields[$name][intval($r->id)] = array ( 'idnumber' => $r->idnumber, 'value' => $value);
                    }
                }
            }
        }
    }
    $rs->close();

    // Process any data that must have unique values.
    foreach ($unique_fields as $fieldname => $fielddata) {

        // We need to get all the field values into
        // an array so we can extract the duplicate values.
        $field_values = array ();
        foreach ($fielddata as $id => $values) {
            $field_values[$id] = $values['value'];
        }

        // Build up an array from the field values
        // where there are duplicates.
        $error_ids = array ();
        foreach ($field_values as $id => $value) {
            // Get a list of elements that match the current value.
            $matches = array_keys($field_values, $value);
            // If we've got more than one then we've got duplicates.
            foreach ($matches as $row_id) {
                $log_data = array('idnumber' => $fielddata[$id]['idnumber'], 'fieldname' => $fieldname, 'value' => $fielddata[$id]['value']);
                $rowerrors[$row_id][$fieldname] = get_string('fieldmustbeunique', 'tool_totara_sync', $log_data);
            }
        }
    }
    return $rowerrors;
}

function get_customfieldsdb() {
    global $DB;

    $customfieldsdb = array();
    $rs = $DB->get_recordset('user_info_field', array(), '', 'id,shortname,datatype,required,defaultdata,locked,forceunique,param1');
    foreach ($rs as $r) {
        $customfieldsdb['customfield_'.$r->shortname]['id'] = $r->id;
        $customfieldsdb['customfield_'.$r->shortname]['required'] = $r->required;
        $customfieldsdb['customfield_'.$r->shortname]['forceunique'] = $r->forceunique;
        $customfieldsdb['customfield_'.$r->shortname]['default'] = $r->defaultdata;

        if ($r->datatype == 'menu') {
            // Set all options to lower case to match values to options without case sensitivity.
            $options = explode("\n", core_text::strtolower($r->param1));
            $customfieldsdb['customfield_'.$r->shortname]['menu_options'] = $options;
        }
    }
    $rs->close();
    return $customfieldsdb;
}

function get_invalid_org_pos($synctable, $table, $field, $identifier) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $sql = "SELECT s.id, s.idnumber, s.$field
              FROM {{$synctable}} s
   LEFT OUTER JOIN {{$table}} t ON s.$field = t.idnumber
             WHERE s.$field IS NOT NULL
               AND s.$field != ''
               AND t.idnumber IS NULL";
    if (!get_config('totara_sync_element_user', 'sourceallrecords')) {
        $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
        $params[0] = 0;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$field] = get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function get_invalid_roles($synctable, $synctable_clone, $role) {
    global $DB;

    $idnumberfield = "{$role}idnumber";
    $params = array();
    $rowerrors = array();
    $sql = "SELECT s.id, s.idnumber, s.{$idnumberfield}
              FROM {{$synctable}} s
   LEFT OUTER JOIN {user} u
                ON s.{$idnumberfield} = u.idnumber
             WHERE s.{$idnumberfield} IS NOT NULL
               AND s.{$idnumberfield} != ''
               AND u.idnumber IS NULL
               AND s.{$idnumberfield} NOT IN
                   (SELECT idnumber FROM {{$synctable_clone}})";
    if (!is_source_all_records()) {
        $sql .= ' AND s.deleted = ?'; // Avoid users that will be deleted.
        $params[0] = 0;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$idnumberfield] = get_string($role.'xnotexist', 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function check_self_assignment($synctable, $role, $identifier) {
    global $DB;

    $params = array();
    $rowerrors = array();
    $sql = "SELECT id, idnumber
              FROM {{$synctable}}
             WHERE idnumber = $role";
    if (!is_source_all_records()) {
        $sql .= ' AND deleted = ?'; // Avoid users that will be deleted.
        $params[0] = 0;
    }
    $rs = $DB->get_recordset_sql($sql, $params);
    foreach ($rs as $r) {
        $rowerrors[$r->id][$role] = get_string($identifier, 'tool_totara_sync', $r);
    }
    $rs->close();

    return $rowerrors;
}

function is_source_all_records() {
    return get_config('totara_sync_element_user', 'sourceallrecords');
}

function merge_error_array($error1, $error2) {
    foreach (array_keys($error2) as $index) {
        if (empty($error1[$index])) {
            $error1[$index] = array();
        }
        $error1[$index]+= $error2[$index];
    }
    return $error1;
}
