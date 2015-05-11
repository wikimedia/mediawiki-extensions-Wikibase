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
	 * @param EntityId[] $subscriptions
	 *
	 * @return SubscriptionLookup
	 */
	private function getSubscriptionLookup( array $subscriptions ) {
		$lookup = $this->getMock( 'Wikibase\Store\SubscriptionLookup' );

		$lookup->expects( $this->any() )
			->method( 'getSubscriptions' )
			->will( $this->returnCallback( function ( $siteId, array $ids ) use ( $subscriptions ) {
				return array_intersect( $subscriptions, $ids );
			} ) );

		return $lookup;
	}

	public function provideGetSubscriptions() {
		$p1 = new PropertyId( 'P1' );
		$p3 = new PropertyId( 'P3' );
		$q2 = new ItemId( 'Q2' );
		$q7 = new ItemId( 'Q7' );

		return array(
			'empty' => array(
				array(),
				array(),
				array(),
				array(),
			),
			'empty request' => array(
				array( $p1 ),
				array( $q7 ),
				array(),
				array(),
			),
			'empty lookups' => array(
				array(),
				array(),
				array( $p1, $q2, $q7, $p3 ),
				array(),
			),
			'partial' => array(
				array( $p1 ),
				array( $q7 ),
				array( $p1, $q2, $q7, $p3 ),
				array( $p1, $q7 ),
			),
			'primary covers' => array(
				array( $p1 ),
				array( $q7 ),
				array( $p1 ),
				array( $p1 ),
			),
			'secondary covers' => array(
				array( $p1 ),
				array( $q7 ),
				array( $q7 ),
				array( $q7 ),
			),
		);
	}

	/**
	 * @dataProvider provideGetSubscriptions
	 */
	public function testGetSubscriptions( $primary, $secondary, $requested, $expected ) {
		$primary = $this->getSubscriptionLookup( $primary );
		$secondary = $this->getSubscriptionLookup( $secondary );

		$lookup = new DualSubscriptionLookup( $primary, $secondary );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', $requested );

		$this->assertEquals( $this->getIdStrings( $expected ), $this->getIdStrings( $subscriptions ) );
	}

	private function getIdStrings( array $ids ) {
		$strings = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $ids );

		sort( $strings );
		return $strings;
	}

}
