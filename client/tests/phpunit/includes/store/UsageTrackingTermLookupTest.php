<?php

namespace Wikibase\Test;

use Wikibase\Client\Store\UsageTrackingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\TermLookup;

/**
 * @covers Wikibase\Lib\Store\UsageTrackingTermLookup
 *
 * @group Wikibase
 * @group WikibaseLib
 * @group WikibaseStore
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class UsageTrackingTermLookupTest extends \MediaWikiTestCase {

	/**
	 * @return TermLookup
	 */
	private function getMockTermLookup( $method, $p1, $p2 ) {
		$mockLookup = $this->getMock( 'Wikibase\Lib\Store\TermLookup' );
		$mockLookup->expects( $this->once() )
			->method( $method )
			->with( $p1, $p2 )
			->will( $this->returnValue( 'TEST' ) );

		return $mockLookup;
	}

	public function testGetLabel() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getLabel', $q1, 'en' );

		$mockAccumulator = $this->getMock( 'Wikibase\Client\Usage\UsageAccumulator' );
		$mockAccumulator->expects( $this->once() )
			->method( 'addLabelUsage' )
			->with( $q1, 'en' );

		$lookup = new UsageTrackingTermLookup( $mockLookup, $mockAccumulator );
		$lookup->getLabel( $q1, 'en' );
	}

	public function testGetDescription() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getDescription', $q1, 'en' );

		$mockAccumulator = $this->getMock( 'Wikibase\Client\Usage\UsageAccumulator' );
		$mockAccumulator->expects( $this->never() )
			->method( 'addLabelUsage' )
			->with( $q1, 'en' );

		$lookup = new UsageTrackingTermLookup( $mockLookup, $mockAccumulator );
		$lookup->getDescription( $q1, 'en' );
	}

	public function testGetLabels() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getLabels', $q1, array( 'en', 'de' ) );

		$mockAccumulator = $this->getMock( 'Wikibase\Client\Usage\UsageAccumulator' );
		$mockAccumulator->expects( $this->at( 0 ) )
			->method( 'addLabelUsage' )
			->with( $q1, 'en' );
		$mockAccumulator->expects( $this->at( 1 ) )
			->method( 'addLabelUsage' )
			->with( $q1, 'de' );

		$lookup = new UsageTrackingTermLookup( $mockLookup, $mockAccumulator );
		$lookup->getLabels( $q1, array( 'en', 'de' ) );
	}

	public function testGetDescriptions() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getDescriptions', $q1, array( 'en', 'de' ) );

		$mockAccumulator = $this->getMock( 'Wikibase\Client\Usage\UsageAccumulator' );
		$mockAccumulator->expects( $this->never() )
			->method( 'addLabelUsage' )
			->with( $this->onConsecutiveCalls() );

		$lookup = new UsageTrackingTermLookup( $mockLookup, $mockAccumulator );
		$lookup->getDescriptions( $q1, array( 'en', 'de' ) );
	}

}
