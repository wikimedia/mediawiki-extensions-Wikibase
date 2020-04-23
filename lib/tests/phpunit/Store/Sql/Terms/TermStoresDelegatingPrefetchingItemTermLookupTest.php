<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup;

/**
 * @group Wikibase
 *
 * @covers \Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup
 */
class TermStoresDelegatingPrefetchingItemTermLookupTest extends TestCase {

	/** @var ItemId */
	private $normalizedStoreItemId;
	/** @var ItemId */
	private $wbTermsStoreItemId;
	/** @var DataAccessSettings */
	private $dataAccessSettings;
	/** @var PrefetchingTermLookup */
	private $normalizedStorePrefetchingTermLookup;
	/** @var PrefetchingTermLookup */
	private $wbTermsStorePrefetchingTermLookup;

	public function setUp(): void {
		$this->normalizedStoreItemId = ItemId::newFromNumber( 123 );
		$this->wbTermsStoreItemId = ItemId::newFromNumber( 321 );

		$this->dataAccessSettings = $this->createMock( DataAccessSettings::class );
		$this->dataAccessSettings->method( 'useNormalizedItemTerms' )
			->willReturnCallback( function ( $numericItemId ) {
				return $numericItemId === 123;
			} );

		$this->normalizedStorePrefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
		$this->wbTermsStorePrefetchingTermLookup = $this->createMock( PrefetchingTermLookup::class );
	}

	public function testGetPrefetchedTerm() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getPrefetchedTerm' )
			->with( $this->normalizedStoreItemId, TermTypes::TYPE_LABEL, 'en' )
			->willReturn( 'a' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getPrefetchedTerm' )
			->with( $this->wbTermsStoreItemId, TermTypes::TYPE_DESCRIPTION, 'de' )
			->willReturn( 'b' );

		$testSubject = $this->newTestSubject();
		$this->assertEquals(
			'a',
			$testSubject->getPrefetchedTerm( $this->normalizedStoreItemId, TermTypes::TYPE_LABEL, 'en' )
		);
		$this->assertEquals(
			'b',
			$testSubject->getPrefetchedTerm( $this->wbTermsStoreItemId, TermTypes::TYPE_DESCRIPTION, 'de' )
		);
	}

	public function testPrefetchTerms() {
		$termTypes = [ TermTypes::TYPE_LABEL, TermTypes::TYPE_DESCRIPTION ];
		$langCodes = [ 'en', 'de' ];

		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'prefetchTerms' )->with( [ $this->normalizedStoreItemId ], $termTypes, $langCodes );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'prefetchTerms' )->with( [ $this->wbTermsStoreItemId ], $termTypes, $langCodes );

		$this->newTestSubject()->prefetchTerms(
			[ $this->normalizedStoreItemId, $this->wbTermsStoreItemId ],
			$termTypes,
			$langCodes
		);
	}

	public function testGetLabel() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $this->normalizedStoreItemId, 'en' )
			->willReturn( 'a' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabel' )
			->with( $this->wbTermsStoreItemId, 'de' )
			->willReturn( 'b' );

		$testSubject = $this->newTestSubject();
		$this->assertEquals(
			'a',
			$testSubject->getLabel( $this->normalizedStoreItemId, 'en' )
		);
		$this->assertEquals(
			'b',
			$testSubject->getLabel( $this->wbTermsStoreItemId, 'de' )
		);
	}

	public function testGetDescription() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $this->normalizedStoreItemId, 'en' )
			->willReturn( 'a' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescription' )
			->with( $this->wbTermsStoreItemId, 'de' )
			->willReturn( 'b' );

		$testSubject = $this->newTestSubject();
		$this->assertEquals(
			'a',
			$testSubject->getDescription( $this->normalizedStoreItemId, 'en' )
		);
		$this->assertEquals(
			'b',
			$testSubject->getDescription( $this->wbTermsStoreItemId, 'de' )
		);
	}

	public function testGetLabels() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $this->normalizedStoreItemId, [ 'en' ] )
			->willReturn( [ 'a', 'b' ] );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabels' )
			->with( $this->wbTermsStoreItemId, [ 'de' ] )
			->willReturn( [ 'c', 'd' ] );

		$testSubject = $this->newTestSubject();
		$this->assertEquals(
			[ 'a', 'b' ],
			$testSubject->getLabels( $this->normalizedStoreItemId, [ 'en' ] )
		);
		$this->assertEquals(
			[ 'c', 'd' ],
			$testSubject->getLabels( $this->wbTermsStoreItemId, [ 'de' ] )
		);
	}

	public function testGetDescriptions() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $this->normalizedStoreItemId, [ 'en' ] )
			->willReturn( [ 'a', 'b' ] );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescriptions' )
			->with( $this->wbTermsStoreItemId, [ 'de' ] )
			->willReturn( [ 'c', 'd' ] );

		$testSubject = $this->newTestSubject();
		$this->assertEquals(
			[ 'a', 'b' ],
			$testSubject->getDescriptions( $this->normalizedStoreItemId, [ 'en' ] )
		);
		$this->assertEquals(
			[ 'c', 'd' ],
			$testSubject->getDescriptions( $this->wbTermsStoreItemId, [ 'de' ] )
		);
	}

	private function newTestSubject() {
		return new TermStoresDelegatingPrefetchingItemTermLookup(
			$this->dataAccessSettings,
			$this->normalizedStorePrefetchingTermLookup,
			$this->wbTermsStorePrefetchingTermLookup
		);
	}
}
