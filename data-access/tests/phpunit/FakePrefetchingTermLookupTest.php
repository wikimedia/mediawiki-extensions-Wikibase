<?php

namespace Wikibase\DataAccess\Tests;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers \Wikibase\DataAccess\Tests\FakePrefetchingTermLookup
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class FakePrefetchingTermLookupTest extends TestCase {

	public function testGetLabels() {
		$lookup = new FakePrefetchingTermLookup();

		$this->assertEquals(
			[ 'en' => 'Q1 en label', 'de' => 'Q1 de label' ],
			$lookup->getLabels( new ItemId( 'Q1' ), [ 'en', 'de' ] )
		);
	}

	public function testGetLabel() {
		$lookup = new FakePrefetchingTermLookup();

		$this->assertSame( 'Q1 en label', $lookup->getLabel( new ItemId( 'Q1' ), 'en' ) );
	}

	public function testGetDescriptions() {
		$lookup = new FakePrefetchingTermLookup();

		$this->assertEquals(
			[ 'en' => 'Q1 en description', 'de' => 'Q1 de description' ],
			$lookup->getDescriptions( new ItemId( 'Q1' ), [ 'en', 'de' ] )
		);
	}

	public function testGetDescription() {
		$lookup = new FakePrefetchingTermLookup();

		$this->assertSame( 'Q1 en description', $lookup->getDescription( new ItemId( 'Q1' ), 'en' ) );
	}

	public function testGivenPrefetchTermsCalledBefore_getPrefetchTermReturnsValue() {
		$lookup = new FakePrefetchingTermLookup();

		$lookup->prefetchTerms( [ new ItemId( 'Q1' ) ], [ 'label' ], [ 'en' ] );

		$this->assertSame( 'Q1 en label', $lookup->getPrefetchedTerm( new ItemId( 'Q1' ), 'label', 'en' ) );
	}

	public function testGivenNoPrefetchTermsCallBefore_getPrefetchTermReturnsNull() {
		$lookup = new FakePrefetchingTermLookup();

		$this->assertNull( $lookup->getPrefetchedTerm( new ItemId( 'Q1' ), 'label', 'en' ) );
	}

	public function testGetPrefetchedAliases() {
		$lookup = new FakePrefetchingTermLookup();

		$id = new ItemId( 'Q1' );

		$lookup->prefetchTerms( [ $id ], [ 'alias' ], [ 'fr' ] );

		$this->assertEquals(
			[ 'Q1 fr alias 1', 'Q1 fr alias 2' ],
			$lookup->getPrefetchedAliases( $id, 'fr' )
		);
	}

}
