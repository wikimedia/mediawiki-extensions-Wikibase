<?php

namespace Wikibase\Client\Test\Store;

use PHPUnit_Framework_Assert;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\Usage\SubscriptionManager;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Client\Usage\UsageLookup;
use Wikibase\Client\Usage\UsageTracker;

/**
 * @covers Wikibase\Client\Store\UsageUpdater
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UsageUpdaterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @param array $oldUsage
	 *
	 * @return UsageTracker
	 */
	private function getUsageTracker( array $oldUsage = null ) {
		$usage = $oldUsage;

		$mock = $this->getMock( 'Wikibase\Client\Usage\UsageTracker' );

		if ( $oldUsage === null ) {
			$mock->expects( $this->never() )
				->method( 'trackUsedEntities' );
		} else {
			$mock->expects( $this->once() )
				->method( 'trackUsedEntities' )
				->will( $this->returnCallback(
					function ( $pageId, $newUsage ) use ( &$usage ) {
						$oldUsage = $usage;
						$usage = $newUsage;
						return $oldUsage;
					} ) );
		}

		return $mock;
	}

	/**
	 * @return UsageLookup
	 */
	private function getUsageLookup( array $unusedEntities = null ) {
		$mock = $this->getMock( 'Wikibase\Client\Usage\UsageLookup' );

		if ( $unusedEntities === null ) {
			$mock->expects( $this->never() )
				->method( 'getUnusedEntities' );
		} else {
			$mock->expects( $this->once() )
				->method( 'getUnusedEntities' )
				->will( $this->returnValue( $unusedEntities ) );
		}

		return $mock;
	}

	/**
	 * @return SubscriptionManager
	 */
	private function getSubscriptionManager( $wiki, $subscribe, $unsubscribe ) {
		$mock = $this->getMock( 'Wikibase\Client\Usage\SubscriptionManager' );

		if ( empty( $subscribe ) && empty( $unsubscribe ) ) {
			$mock->expects( $this->never() )
				->method( 'subscribe' );

			$mock->expects( $this->never() )
				->method( 'unsubscribe' );
		} else {
			$mock->expects( $this->once() )
				->method( 'subscribe' )
				->with( $this->equalTo( $wiki ), $this->callback(
					function ( $actualSubscribe ) use ( $subscribe ) {
						return !count( array_diff( $subscribe, $actualSubscribe ) );
					}
				) );

			$mock->expects( $this->once() )
				->method( 'unsubscribe' )
				->with( $this->equalTo( $wiki ), $this->callback(
					function ( $actualUnsubscribe ) use ( $unsubscribe ) {
						return !count( array_diff( $unsubscribe, $actualUnsubscribe ) );
					}
				) );
		}

		return $mock;
	}

	/**
	 * @return UsageUpdater
	 */
	private function getUsageUpdater( $oldUsage, $unusedEntities, array $subscribe, array $unsubscribe ) {
		return new UsageUpdater(
			'testwiki',
			$this->getUsageTracker( $oldUsage ),
			$this->getUsageLookup( $unusedEntities ),
			$this->getSubscriptionManager( 'testwiki', $subscribe, $unsubscribe )
		);
	}

	public function updateUsageForPageProvider() {
		$q1 = new ItemId( 'Q1' );
		$q2 = new ItemId( 'Q2' );
		$q3 = new ItemId( 'Q3' );

		return array(
			'empty' => array(
				array(),
				array(),
				null, // null means "bail out before checking unused entities"
				array(),
				array(),
			),

			'unchanged' => array(
				array( 'label' => array( $q1, $q2 ) ),
				array( 'all' => array( $q2 ), 'label' => array( $q1 ) ),
				null, // null means "bail out before checking unused entities"
				array(),
				array(),
			),

			'no added or unused' => array(
				array( 'label' => array( $q1, $q2 ) ),
				array(),
				array(), // entities were removed, but none are now unused
				array(),
				array(),
			),

			'subscriptions updated' => array(
				array( 'label' => array( $q1, $q2 ) ),
				array( 'label' => array( $q1, $q3 ) ),
				array( $q2 ),
				array( $q3 ),
				array( $q2 ),
			),
		);
	}

	/**
	 * @dataProvider updateUsageForPageProvider
	 */
	public function testUpdateUsageForPage( $oldUsage, $newUsage, $unusedEntities, $subscribe, $unsubscribe ) {
		$updater = $this->getUsageUpdater( $oldUsage, $unusedEntities, $subscribe, $unsubscribe );

		// assertions are done by the mock double
		$updater->updateUsageForPage( 23, $newUsage );
	}

	public function updateUsageForPageInvalidArgumentProvider() {
		$q1 = new ItemId( 'Q1' );

		return array(
			'string id' => array( 'foo', array() ),
			'bad array structure' => array( 7, array( $q1 ) ),
			'bad array keys' => array( 7, array( array( $q1 ) ) ),
		);
	}

	/**
	 * @dataProvider updateUsageForPageInvalidArgumentProvider
	 */
	public function testUpdateUsageForPage_InvalidArgumentException( $pageId, $newUsage ) {
		$updater = $this->getUsageUpdater( null, null, array(), array() );

		$this->setExpectedException( 'InvalidArgumentException' );
		$updater->updateUsageForPage( $pageId, $newUsage );
	}

}
