<?php

namespace SimpleSAML\Test\Module\mongo\Store;

use PHPUnit\Framework\TestCase;
use MongoDb\Driver\Manager;
use MongoDb\Driver\Query;
use MongoDb\Driver\BulkWrite;
use SimpleSAML\Configuration;
use SimpleSAML\Module;
use SimpleSAML\Module\mongodb\StoreInterface\Store;
use SimpleSAML\Store\StoreFactory;

final class StoreTest extends TestCase
{
    public function testSingleHostConnection()
    {
        Configuration::setConfigDir(__DIR__ . '/fixture/single-host');
        new Store();
        $this->assertTrue(true);
    }

    public function testGet()
    {
        $store = new Store();
        $manager = $store->getManager();

        // Remove everything in the collection first
        $this->clearSessions($manager);

        $this->assertEquals(0, $this->getSessionCount($manager));

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;

        // Check that the non-existent Session does not exist
        $result = $store->get($type, $key);
        $this->assertNull($result);

        $store->set($type, $key, $value, $expire);

        $this->assertEquals(1, $this->getSessionCount($manager));

        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);

        $this->assertEquals(1, $this->getSessionCount($manager));
    }

    public function testExpiredGet()
    {
        $store = new Store();
        $manager = $store->getManager();

        $this->clearSessions($manager);

        $this->assertEquals(0, $this->getSessionCount($manager));

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = 0;
        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $this->getSessionCount($manager));

        $result = $store->get($type, $key);
        $this->assertNull($result);
        $this->assertEquals(0, $this->getSessionCount($manager));
    }

    public function testSet()
    {
        $store = new Store();
        $manager = $store->getManager();

        $this->clearSessions($manager);

        $this->assertEquals(0, $this->getSessionCount($manager));

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;

        $result = $store->get($type, $key);
        $this->assertNull($result);

        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $this->getSessionCount($manager));

        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);
        $this->assertEquals(1, $this->getSessionCount($manager));

        $value = array('some' => 'otherthing');
        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $this->getSessionCount($manager));
        $result = $store->get($type, $key);
        $this->assertEquals($result, $value);
        $this->assertEquals(1, $this->getSessionCount($manager));
    }

    public function testDelete()
    {
        $store = new Store();
        $manager = $store->getManager();

        $this->clearSessions($manager);

        $this->assertEquals(0, $this->getSessionCount($manager));

        $type = 'session';
        $key = 'SESSION_ID';
        $value = array('some' => 'thing');
        $expire = time() + 1000000;
        $store->set($type, $key, $value, $expire);
        $this->assertEquals(1, $this->getSessionCount($manager));

        $store->delete($type, $key);
        $this->assertEquals(0, $this->getSessionCount($manager));
    }

    public function testResolveClass(): void
    {
        symlink(__DIR__ . '/../../src', __DIR__ . '/../../vendor/simplesamlphp/simplesamlphp/modules/mongodb');
        $store = StoreFactory::getInstance('mongodb:Store');

        $this->assertEquals(Store::class, get_class($store));
        $this->assertEquals(Store::class, Module::resolveClass('mongodb:Store', 'StoreInterface'));

        unlink(__DIR__ . '/../../vendor/simplesamlphp/simplesamlphp/modules/mongodb');
    }

    protected function getSessionNamespace()
    {
        return getenv('DB_MONGODB_DATABASE') . '.session';
    }

    /**
     * Purge all records in the Sessions collection
     * @param \MongoDb\Driver\Manager $manager
     */
    protected function clearSessions(Manager $manager)
    {
        $bulk = new BulkWrite();
        $bulk->delete([]);

        $manager->executeBulkWrite($this->getSessionNamespace(), $bulk);
    }

    /**
     * Get count of a given namespace (database/collection pair)
     * @param \MongoDb\Driver\Manager $manager
     * @return integer
     */
    protected function getSessionCount(Manager $manager)
    {
        $countQuery = new Query([]);
        $countQueryResult = $manager->executeQuery($this->getSessionNamespace(), $countQuery);

        $countResultArray = !empty($countQueryResult) ? $countQueryResult->toArray() : [];

        return count($countResultArray);
    }
}
