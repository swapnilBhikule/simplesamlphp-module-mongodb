<?php

$config = array(
    'host' => getenv('DB_MONGODB_HOST'),
    'port' => getenv('DB_MONGODB_PORT'),
    'username' => getenv('DB_MONGODB_USERNAME'),
    'password' => getenv('DB_MONGODB_PASSWORD'),
    'database' => getenv('DB_MONGODB_DATABASE'),
    'authSource' => getenv('DB_MONGODB_DATABASE'),
    'isReplicaConnectionString' => false,
);

if(getenv('DB_DEFAULT_CONNECTION') === 'mongodb_replica') {
    $config['replicaSet'] = getenv('DB_MONGODB_REPLICASET');
    $config['readPreference'] = getenv('DB_MONGODB_READ_PREFERENCE');
}

if(getenv('DB_DEFAULT_CONNECTION') === 'mongodb_replica_string') {
    $config['dsn'] = getenv('DB_MONGODB_DSN');
    $config['isReplicaConnectionString'] = true;
}
