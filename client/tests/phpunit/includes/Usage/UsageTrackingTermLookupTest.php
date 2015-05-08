<?php

namespace Wikibase\Client\Tests\Usage;

use Wikibase\Client\Usage\UsageTrackingTermLookup;
use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\TermLookup;

/**
 * @covers Wikibase\Client\Usage\UsageTrackingTermLookup
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

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getLabel( $q1, 'en' );

		$this->assertCount( 1, $acc->getUsages() );
	}

	public function testGetDescription() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getDescription', $q1, 'en' );

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getDescription( $q1, 'en' );

		$this->assertCount( 0, $acc->getUsages() );
	}

	public function testGetLabels() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getLabels', $q1, array( 'en', 'de' ) );

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getLabels( $q1, array( 'en', 'de' ) );

		$this->assertCount( 2, $acc->getUsages() );
	}

	public function testGetDescriptions() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getDescriptions', $q1, array( 'en', 'de' ) );

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getDescriptions( $q1, array( 'en', 'de' ) );

		$this->assertCount( 0, $acc->getUsages() );
	}

}
