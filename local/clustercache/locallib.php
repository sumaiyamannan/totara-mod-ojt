<?php
function clustercache_setup_cache (){
    // Set up the local cache.
    cache_helper::update_definitions();
    $cachename = 'Local cache';
    $localcreated = clustercache_create_local_file_store($cachename);
    $writer = cache_config_writer::instance();
    $config = cache_config::instance();

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

    if (extension_loaded('redis')) {
        // Set up the Redis Sentinel
        $cachename = 'Redis Sentinel';
        $rediscreated = clustercache_create_redis_sentinel_store($cachename);
        if (!$rediscreated) {
            print "failed to create redis sentinel store<br/>\n";
            return false;
        }

    } else {

        // Set up the memcache cluster.
        $cachename = 'Cluster memcache';
        $memcachecreated = clustercache_create_memcache_cluster_store($cachename);
        if (!$memcachecreated) {
            print "failed to create clustered memcache store<br/>\n";
            return false;
        }

    }

    // Great - the plugin instance exists.
    // Set default stores for application cache types ('modes').
    foreach ($config->get_mode_mappings() as $defaultmapping) {
        $defaultmappings[$defaultmapping['mode']] = array($defaultmapping['store']);
    }
    $defaultmappings[cache_store::MODE_APPLICATION] =  array($cachename);

    $result = $writer->set_mode_mappings($defaultmappings);
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
    $metadatafilepath = $CFG->dirroot . '/site-environment-data.php';
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
    $data->path = "/var/cache/appcache/$siteidentifier";
    $config = cache_administration_helper::get_store_configuration_from_data($data);
    $writer = cache_config_writer::instance();

    // Is this only an update?
    $updatestore = array_key_exists($storename, $stores) ? true : false;

    if ($updatestore) {
        $result = $writer->edit_store_instance($data->name, $data->plugin, $config);
        print "Update local store named $storename<br/>\n";
    } else {
        $result = $writer->add_store_instance($data->name, $data->plugin, $config);
        print "Add local store named $storename<br>\n";
    }

    if (!$result) {
        print "FAILED to create/update local store named $storename<br/>\n";
    }

    return $result;
}

function clustercache_create_memcache_cluster_store($storename) {
    global $CFG;

    $metadatafilepath = $CFG->dirroot . '/site-environment-data.php';
    if (!file_exists($metadatafilepath)) {
        // Unable to get needed info.
        print "metadata file path ($metadatafilepath) doesn't exist<br/>\n";
        return false;
    }
    include($metadatafilepath);

    $stores = cache_administration_helper::get_store_instance_summaries();
    $writer = cache_config_writer::instance();

    $data = new stdClass();
    $data->plugin = 'memcache';
    // Set an arbitary name, but consistent accross our many sites.
    $data->name = $storename;
    $instance = cache_config::instance();
    $stores = $instance->get_all_stores();

    $data->lock = 'cachelock_file_default';
    $data->servers = 'cache-local:11212';
    $data->clustered = 1;
    $data->setservers = array();
    $setservers = array('cache-local' => 11212, 'cache-remote-a' => 11212);
    foreach ($setservers as $server => $port) {
        if ($environment != "prod" && !clustercache_can_connect_to_server($server, $port)) {
            print "WARNING: cannot connect to memcache server $server:$port - skipping configuration of this server<br/>\n";
            continue;
        }
        $data->setservers[] = "{$server}:{$port}";
    }
    $data->setservers = implode("\n", $data->setservers);

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

    // Is this an update?
    $updatestore = array_key_exists($storename, $stores) ? true : false;

    if ($updatestore) {
        $result = $writer->edit_store_instance($data->name, $data->plugin, $config);
        print "Update local store named $storename<br/>\n";
    } else {
        $result = $writer->add_store_instance($data->name, $data->plugin, $config);
        print "Create local store named $storename<br/>\n";
    }
    if (!$result) {
        print "FAILED to create/update cluster memcache store '$storename'<br/>\n";
    }

    return $result;
}


function clustercache_create_redis_sentinel_store($storename) {
    global $CFG;

    $metadatafilepath = $CFG->dirroot . '/site-environment-data.php';
    if (!file_exists($metadatafilepath)) {
        // Unable to get needed info.
        print "metadata file path ($metadatafilepath) doesn't exist<br/>\n";
        return false;
    }
    include($metadatafilepath);

    $stores = cache_administration_helper::get_store_instance_summaries();
    $writer = cache_config_writer::instance();

    $data = new stdClass();
    $data->plugin = 'redissentinel';
    // Set an arbitary name, but consistent accross our many sites.
    $data->name = $storename;
    $instance = cache_config::instance();
    $stores = $instance->get_all_stores();

    $data->setservers = array();
    $servers = array('sentinel_local' => 26379, 'sentinel_a' => 26379, 'sentinel_b' => 26379);
    foreach ($servers as $server => $port) {
        if ($environment != "prod" && !clustercache_can_connect_to_server($server, $port)) {
            print "WARNING: cannot connect to Redis sentinel server $server:$port - skipping configuration of this server<br/>\n";
            continue;
        }
        $data->server[] = "{$server}:{$port}";
    }
    $data->server = implode(",", $data->server);

    if (empty($sitename)) {
        // Can't get enough info to set prefix properly.
        print "can't find sitename<br/>\n";
        return false;
    }

    $data->prefix = "$sitename-$environment-$apptype";

    if (empty($CFG->session_redissentinel_master_group)) {
        print "can't find master group name<br/>\n";
        return false;
    }

    $data->master_group = $CFG->session_redissentinel_master_group;


    $config = cache_administration_helper::get_store_configuration_from_data($data);
    $writer = cache_config_writer::instance();


    // Is this an update?
    $updatestore = array_key_exists($storename, $stores) ? true : false;

    if ($updatestore) {
        $result = $writer->edit_store_instance($data->name, $data->plugin, $config);
        print "Update local store named $storename<br/>\n";
    } else {
        $result = $writer->add_store_instance($data->name, $data->plugin, $config);
        print "Create local store named $storename<br/>\n";
    }
    if (!$result) {
        print "FAILED to create/update Redis sentinel store '$storename'<br/>\n";
    }

    return $result;
}
function clustercache_can_connect_to_server($server, $port) {
    $fp = @fsockopen($server, $port, $errno, $errstr, 4);
    if (!$fp) {
        return false;
    } else {
        fclose($fp);
        return true;
    }
}
