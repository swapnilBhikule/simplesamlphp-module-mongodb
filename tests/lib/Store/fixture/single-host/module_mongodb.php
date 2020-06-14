<?php

$config = array(
    'host' => getenv('DB_MONGODB_HOST'),
    'port' => getenv('DB_MONGODB_PORT'),
    'username' => getenv('DB_MONGODB_USERNAME'),
    'password' => getenv('DB_MONGODB_PASSWORD'),
    'database' => getenv('DB_MONGODB_DATABASE'),
    'dsn' = getenv('DB_MONGODB_DSN');
    'isReplicaConnectionString' = false;
);

