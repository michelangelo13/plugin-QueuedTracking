<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\QueuedTracking\tests\Framework\TestCase;

use Piwik\Plugins\QueuedTracking\Queue;
use Piwik\Plugins\QueuedTracking\QueuedTracking;
use Piwik\Tests\Framework\Mock\Tracker\RequestSet;
use Piwik\Tracker\RequestSet as PiwikRequestSet;
use Piwik\Tracker\Request;

/**
 * @group QueuedTracking
 * @group Redis
 */
class IntegrationTestCase extends \Piwik\Tests\Framework\TestCase\IntegrationTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->configureRedisTestInstance();
    }

    public static function tearDownAfterClass()
    {
        Queue\Factory::clearSettings();

        parent::tearDownAfterClass();
    }

    protected function clearRedisDb()
    {
        $backend = $this->createRedisBackend();
        $backend->flushAll();
    }

    protected function createRedisBackend()
    {
        $this->configureRedisTestInstance();
        return Queue\Factory::makeBackend();
    }

    private function configureRedisTestInstance()
    {
        $queuedTracking = new QueuedTracking();
        $queuedTracking->configureQueueTestBackend();
    }

    protected function buildRequestSet($numberOfRequestSets)
    {
        $requests = array();

        for ($i = 0; $i < $numberOfRequestSets; $i++) {
            $requests[] = new Request(array('idsite' => '1', 'index' => $i));
        }

        $set = new RequestSet();
        $set->setRequests($requests);

        return $set;
    }

    protected function assertRequestsAreEqual(PiwikRequestSet $expected, PiwikRequestSet $actual)
    {
        $eState = $expected->getState();
        $aState = $actual->getState();

        $eTime = $eState['time'];
        $aTime = $aState['time'];

        unset($eState['time']);
        unset($aState['time']);

        if (array_key_exists('REQUEST_TIME_FLOAT', $eState['env']['server'])) {
            unset($eState['env']['server']['REQUEST_TIME_FLOAT']);
        }

        if (array_key_exists('REQUEST_TIME_FLOAT', $aState['env']['server'])) {
            unset($aState['env']['server']['REQUEST_TIME_FLOAT']);
        }

        $this->assertGreaterThan(100000, $aTime);
        $this->assertTrue(($aTime - 5 < $eTime) && ($aTime + 5 > $eTime), "$eTime is not nearly $aTime");
        $this->assertEquals($eState, $aState);
    }

    protected function buildRequestSetContainingError($numberOfRequestSets, $indexThatShouldContainError)
    {
        $requests = array();

        for ($i = 0; $i < $numberOfRequestSets; $i++) {
            if ($i === $indexThatShouldContainError) {
                $requests[] = new Request(array('idsite' => '0', 'index' => $i));
            } else {
                $requests[] = new Request(array('idsite' => '1', 'index' => $i));
            }

        }

        $set = new RequestSet();
        $set->setRequests($requests);

        return $set;
    }


}
