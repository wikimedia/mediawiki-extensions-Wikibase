<?php

namespace Wikibase\Test;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Store\SiteLinkSubscriptionLookup;

/**
 * @covers Wikibase\Store\SiteLinkSubscriptionLookup
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group WikibaseChange
 * @group WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class SiteLinkSubscriptionLookupTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return SiteLinkLookup
	 */
	private function getSiteLinkLookup() {
		$lookup = $this->getMock( 'Wikibase\Lib\Store\SiteLinkLookup' );

		$lookup->expects( $this->any() )
			->method( 'getLinks' )
			->will( $this->returnCallback( array( $this, 'getLinks' ) ) );

		return $lookup;
	}

	public function getLinks( array $numericIds = array(), array $siteIds = array(), array $pageNames = array() ) {
		$links = array(
			array( 'enwiki', 'Foo', 1 ),
			array( 'enwiki', 'Boo', 3 ),
			array( 'enwiki', 'Queue', 7 ),
			array( 'dewiki', 'Fuh', 2 ),
		);

		return array_filter( $links, function( $link ) use ( $numericIds, $siteIds, $pageNames ) {
			if ( !empty( $numericIds ) && !in_array( $link[2], $numericIds ) ) {
				return false;
			}

			if ( !empty( $siteIds ) && !in_array( $link[0], $siteIds ) ) {
				return false;
			}

			if ( !empty( $pageNames ) && !in_array( $link[1], $pageNames ) ) {
				return false;
			}

			return true;
		} );
	}

	public function testGetSubscriptions() {
		$lookup = new SiteLinkSubscriptionLookup( $this->getSiteLinkLookup() );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', array(
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
			new ItemId( 'Q7' ),
			new PropertyId( 'P3' ),
		) );

		$actual = array_map( function ( EntityId $id ) {
			return $id->getSerialization();
		}, $subscriptions );

		sort( $actual );

		$expected = array( 'Q1', 'Q7' );

		$this->assertEquals( $expected, $actual );
	}

	public function testGetSubscriptions_none() {
		$lookup = new SiteLinkSubscriptionLookup( $this->getSiteLinkLookup() );

		$subscriptions = $lookup->getSubscriptions( 'enwiki', array(
			new PropertyId( 'P3' ), // will be skipped
		) );

		$this->assertEmpty( $subscriptions );
	}

}
