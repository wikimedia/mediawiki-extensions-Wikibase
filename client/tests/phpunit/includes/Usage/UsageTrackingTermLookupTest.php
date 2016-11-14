<?php

namespace Wikibase\Client\Tests\Usage;

use Wikibase\Client\Usage\HashUsageAccumulator;
use Wikibase\Client\Usage\UsageTrackingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\TermLookup;

/**
 * @covers Wikibase\Client\Usage\UsageTrackingTermLookup
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseUsageTracking
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class UsageTrackingTermLookupTest extends \MediaWikiTestCase {

	/**
	 * @param string $method Method name expected to be called once.
	 * @param ItemId $entityId Expected entity id.
	 * @param string|string[] $languageCode Expected language code or array of language codes.
	 *
	 * @return TermLookup
	 */
	private function getMockTermLookup( $method, ItemId $entityId, $languageCode ) {
		$mockLookup = $this->getMock( TermLookup::class );
		$mockLookup->expects( $this->once() )
			->method( $method )
			->with( $entityId, $languageCode )
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
		$mockLookup = $this->getMockTermLookup( 'getLabels', $q1, [ 'en', 'de' ] );

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getLabels( $q1, [ 'en', 'de' ] );

		$this->assertCount( 2, $acc->getUsages() );
	}

	public function testGetDescriptions() {
		$q1 = new ItemId( 'Q1' );
		$mockLookup = $this->getMockTermLookup( 'getDescriptions', $q1, [ 'en', 'de' ] );

		$acc = new HashUsageAccumulator();

		$lookup = new UsageTrackingTermLookup( $mockLookup, $acc );
		$lookup->getDescriptions( $q1, [ 'en', 'de' ] );

		$this->assertCount( 0, $acc->getUsages() );
	}

}
