<?php
function clustercache_setup_cache (){
    // Set up the local cache.
    cache_helper::update_definitions();
    $cachename = 'Local cache';
    $localcreated = clustercache_create_local_file_store($cachename);
    $writer = cache_config_writer::instance();
    if ($localcreated) {
        // Actually map something to the cache store.
        $definitions = array('core/string', 'core/htmlpurifier');
        $mappings = array($cachename);
        foreach ($definitions as $definition) {
            $result = $writer->set_definition_mappings($definition, $mappings);
            if (!$result) {
                print "$definition mapped to $cachename successfully<br/>\n";
            } else {
                print "failed to map $definition to $cachename<br/>\n";
            }
        }
    }

    // Set up the memcache cluster.
    $cachename = 'Cluster memcache';
    $memcachecreated = clustercache_create_memcache_cluster_store($cachename);
    if (!$memcachecreated) {
        print "failed to create clustered memcache store<br/>\n";
        return false;
    }
    // Great - the plugin instance exists.
    // Set default stores for application cache types ('modes').
    $mappings = array(
        cache_store::MODE_APPLICATION => array($cachename),
    );
    $result = $writer->set_mode_mappings($mappings);
    if ($result) {
        print "APPLICATION caches configured to default to $cachename<br/>\n";
    } else {
        print "failed to map $definition to $cachename<br/>\n";
        print "failed to config APPLICATION caches to default to $cachename<br/>\n";
    }

    return true;
}

function clustercache_create_local_file_store($storename) {
    global $CFG;
    $metadatafilepath = $CFG->dirroot . '/deploymentREADME.php';
    if (!file_exists($metadatafilepath)) {
        // Unable to get needed info about where to create cache.
        return 0;
    }
    include($metadatafilepath);
    if (empty($sitename) || empty ($environment) || empty($apptype)) {
        // Needed info about where to create cache not known.
        return 0;
    }
    $siteidentifier = "$sitename-$environment-$apptype"; // eg topnz-prod-moodle
    $stores = cache_administration_helper::get_store_instance_summaries();
    $plugins = cache_administration_helper::get_store_plugin_summaries();

    if (empty($plugins['file'])) {
        // No 'file' type cache plugin defined.
        // Unable to continue.
        return 0;
    }

    $data = new stdClass();
    $data->plugin = 'file';
    // Set an arbitary name, but consistent accross our many sites.
    $data->name = $storename;
    $data->lock = 'cachelock_file_default';
    $data->path = "/var/cache/appcache/$siteidentifier";
    // Check that there is no existing instance with this name.
    if (array_key_exists($storename, $stores)) {
        // Success - there's a plugin of that name already.
        // no need to do anything.
        return 1;
    }
    $writer = cache_config_writer::instance();
    // Check that there is no existing instance with this name.
    $instance = cache_config::instance();
    $stores = $instance->get_all_stores();

    $config = cache_administration_helper::get_store_configuration_from_data($data);
    unset($config['lock']);
    foreach ($writer->get_locks() as $lock => $lockconfig) {
        if ($lock == $data->lock) {
            $config['lock'] = $data->lock;
        }
    }
    if (empty($config['lock'])) {
        // Unable to create the cache instance without a lock mechanism.
        return 0;
    }

    try {
        $result = $writer->add_store_instance($data->name, $data->plugin, $config);
    } catch (cache_exception $e) {
        $message = $e->getMessage();
        if (strpos($message, 'cache/Duplicate name specificed for cache plugin instance.') === 0) {
            // Success - there's already a plugin of that name.
            return true;
        }
        throw $e;
    }
    if ($result) {
        print "Created local store named $storename<br/>\n";
    } else {
        print "FAILED to create local store named $storename<br/>\n";
    }
    return $result;
}
function clustercache_create_memcache_cluster_store($storename) {
    global $CFG;
    $stores = cache_administration_helper::get_store_instance_summaries();
    $writer = cache_config_writer::instance();

    $data = new stdClass();
    $data->plugin = 'memcache';
    // Set an arbitary name, but consistent accross our many sites.
    $data->name = $storename;
    $instance = cache_config::instance();
    $stores = $instance->get_all_stores();
    if (array_key_exists($storename, $stores)) {
        // Success - there's a plugin of that name already.
        // no need to do anything.
        print "$storename already exists<br/>\n";
        return true;
    }
    $data->lock = 'cachelock_file_default';
    $data->servers = 'cache-local:11212';
    $data->clustered = 1;
    $data->setservers = "cache-local:11212\ncache-remote-a:11212";
    $metadatafilepath = $CFG->dirroot . '/deploymentREADME.php';
    if (!file_exists($metadatafilepath)) {
        // Unable to get needed info.
        print "metadata file path ($metadatafilepath) doesn't exist<br/>\n";
        return false;
    }
    include($metadatafilepath);
    if (empty($siteenvironmentid)) {
        // Can't get enough info to set prefix properly.
        print "can't find site environment id<br/>\n";
        return false;
    }
    $data->prefix = $siteenvironmentid;

    $config = cache_administration_helper::get_store_configuration_from_data($data);
    $writer = cache_config_writer::instance();
    unset($config['lock']);
    foreach ($writer->get_locks() as $lock => $lockconfig) {
        if ($lock == $data->lock) {
            $config['lock'] = $data->lock;
        }
    }
    if (empty($config['lock'])) {
        // Unable to create the cache instance without a lock mechanism.
        print "can't find lock mechanism<br/>\n";
        return false;
    }

    try {
        $result = $writer->add_store_instance($data->name, $data->plugin, $config);
    } catch (cache_exception $e) {
        $message = $e->getMessage();
        if (strpos($message, 'cache/Duplicate name specificed for cache plugin instance.') === 0) {
            // Success - there's already a plugin of that name.
            return true;
        }
        throw $e;
    }
    if ($result) {
        print "Created cluster memcache store '$storename'<br/>\n";
    } else {
        print "FAILED to create cluster memcache store '$storename'<br/>\n";
    }
    return $result;
}

