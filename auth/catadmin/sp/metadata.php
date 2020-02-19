<?php

require_once('../setup.php');
require_once('../lib.php');

$download = optional_param('download', '', PARAM_RAW);
if ($download) {
    header('Content-Disposition: attachment; filename=' . $catadminsaml->spname . '.xml');
}

$regenerate = is_siteadmin() && optional_param('regenerate', false, PARAM_BOOL);
if ($regenerate) {
    $file = $catadminsaml->get_file_sp_metadata_file();
    @unlink($file);
}

$xml = auth_catadmin_get_sp_metadata();

if (array_key_exists('output', $_REQUEST) && $_REQUEST['output'] == 'xhtml') {

    $t = new SimpleSAML_XHTML_Template($config, 'metadata.php', 'admin');

    $t->data['header'] = 'saml20-sp';
    $t->data['metadata'] = htmlspecialchars($xml);
    $t->data['metadataflat'] = '$metadata[' . var_export($entityId, TRUE) . '] = ' . var_export($metaArray20, TRUE) . ';';
    $t->data['metaurl'] = $source->getMetadataURL();
    $t->show();
} else {
    // header('Content-Type: application/samlmetadata+xml');
    header('Content-Type: text/xml');
    echo($xml);
}
