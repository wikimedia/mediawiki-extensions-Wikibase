<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Store\DualSubscriptionLookup;
use Wikibase\Store\SubscriptionLookup;

/**
 * @covers Wikibase\Store\DualSubscriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class DualSubscriptionLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SubscriptionLookup
	 */
	private function getSubscriptionLookup( $subscriptions ) {
		$lookup = $this->getMock( 'Wikibase\Store\SubscriptionLookup' );

		$lookup->expects( $this->any() )
			->method( 'getSubscriptions' )
			->will( $this->returnValue( $subscriptions) );

		return $lookup;
	}

	public function testGetSubscriptions() {
		$primary = $this->getSubscriptionLookup( array(
			new PropertyId( 'P1' ),
		) );

		$secondary = $this->getSubscriptionLookup( array(
			new ItemId( 'Q7' ),
		) );

		$lookup = new DualSubscriptionLookup( $primary, $secondary );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', array(
			new PropertyId( 'P1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q7' ),
			new PropertyId( 'P3' ),
		) );

		$actual = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $subscriptions );

		sort( $actual );

		$expected = array( 'P1', 'Q7' );

		$this->assertEquals( $expected, $actual );
	}

}
