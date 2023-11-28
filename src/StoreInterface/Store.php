<?php

namespace SimpleSAML\Module\mongodb\StoreInterface;

/**
 * This file is part of the simplesamlphp-module-mongodb.
 *
 * For the full copyright and license information, please view the LICENSE file that was distributed with this source
 * code.
 *
 * @author Chris Beaton <c.beaton@prolificinteractive.com>
 * @package prolificinteractive/simplesamlphp-module-mongodb
 */

use Exception;
use MongoDb\Driver\Manager;
use MongoDb\Driver\Query;
use MongoDb\Driver\BulkWrite;
use SimpleSAML\Configuration;
use SimpleSAML\Store\StoreInterface;

/**
 * Class sspmod_mongo_Store_Store
 *
 */
class Store implements StoreInterface
{
    protected Manager $manager;
    protected mixed $dbName;

    /**
     * @throws Exception
     */
    public function __construct(array $connectionDetails = [])
    {
        $options = [];
        $config = Configuration::getConfig('module_mongodb.php');
        $connectionDetails = array_merge($config->toArray(), $connectionDetails);
        if (!empty($connectionDetails['replicaSet'])) {
            $options['replicaSet'] = $connectionDetails['replicaSet'];
            if (!empty($connectionDetails['readPreference'])) {
                $options['readPreference'] = $connectionDetails['readPreference'];
            }
        }
        $this->manager = new Manager($this->createConnectionURI($connectionDetails), $options);
        $this->dbName = $connectionDetails['database'];
    }

    /**
     * Builds the connection URI from the specified connection details.
     */
    public static function createConnectionURI(array $connectionDetails = []): string
    {

        // return connection string if database configuration is set to string
        if ($connectionDetails['isReplicaConnectionString'] === true) {
            return $connectionDetails['dsn'];
        }

        $port = $connectionDetails['port'];
        $host = $connectionDetails['host'];
        $seedList = implode(',', array_map(function ($host) use ($port) {
            return "$host:$port";
        }, is_array($host) ? $host : explode(',', $host)));

        return "mongodb://"
            . ((!empty($connectionDetails['username']) && !empty($connectionDetails['password']))
                ? $connectionDetails['username'] . ':' . $connectionDetails['password'] . '@'
                : '')
            . $seedList;
    }

    /**
     * Retrieve a value from the data store.
     *
     * @throws \MongoDB\Driver\Exception\Exception
     */
    public function get(string $type, string $key): mixed
    {
        assert(is_string($type));
        assert(is_string($key));

        $where = [
            'session_id' => $key,
        ];
        $query = new Query($where, ['limit' => 1]);

        $cursor = $this->manager->executeQuery($this->getMongoNamespace($type), $query);

        if (false === ($cursor = current($cursor->toArray()))) {
            return null;
        }

        $cursor = (array) $cursor;

        if (isset($cursor['expire_at'])) {
            $expireAt = $cursor['expire_at'];
            if ($expireAt <= time()) {
                $this->delete($type, $key);

                return null;
            }
        }

        if (!empty($cursor['payload'])) {
            return unserialize($cursor['payload']);
        }

        return $cursor;
    }

    /**
     * Save a value to the data store.
     */
    public function set(string $type, string $key, mixed $value, ?int $expire = null): void
    {
        assert(is_string($type));
        assert(is_string($key));
        assert(is_null($expire) || is_int($expire));

        $document = [
            'session_id' => $key,
            'payload' => serialize($value),
            'expire_at' => $expire
        ];

        $options = [
            'upsert' => true
        ];

        $bulk = new BulkWrite();
        $bulk->update(['session_id' => $key], $document, $options);
        $this->manager->executeBulkWrite($this->getMongoNamespace($type), $bulk);
    }

    /**
     * Delete a value from the data store.
     */
    public function delete(string $type, string $key): void
    {
        assert(is_string($type));
        assert(is_string($key));

        $bulk = new BulkWrite();
        $bulk->delete(['session_id' => $key]);

        $this->manager->executeBulkWrite($this->getMongoNamespace($type), $bulk);
    }

    protected function getMongoNamespace($type): string
    {
        return "$this->dbName.$type";
    }

    public function getManager(): ?Manager
    {
        return !empty($this->manager) ? $this->manager : null;
    }

    public function getDatabase()
    {
        return !empty($this->dbName) ? $this->dbName : null;
    }
}
