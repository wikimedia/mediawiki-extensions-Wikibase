<?php

namespace Wikibase\Lib\Tests\Store\Sql\Terms;

use PHPUnit\Framework\TestCase;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataAccess\PrefetchingTermLookup;
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
			->method( 'getPrefetchedTerm' )->with( $this->normalizedStoreItemId, 'label', 'en' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getPrefetchedTerm' )->with( $this->wbTermsStoreItemId, 'description', 'de' );

		$testSubject = $this->newTestSubject();
		$testSubject->getPrefetchedTerm( $this->normalizedStoreItemId, 'label', 'en' );
		$testSubject->getPrefetchedTerm( $this->wbTermsStoreItemId, 'description', 'de' );
	}

	public function testPrefetchTerms() {
		$termTypes = [ 'label', 'description' ];
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
			->method( 'getLabel' )->with( $this->normalizedStoreItemId, 'en' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabel' )->with( $this->wbTermsStoreItemId, 'de' );

		$testSubject = $this->newTestSubject();
		$testSubject->getLabel( $this->normalizedStoreItemId, 'en' );
		$testSubject->getLabel( $this->wbTermsStoreItemId, 'de' );
	}

	public function testGetDescription() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescription' )->with( $this->normalizedStoreItemId, 'en' );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescription' )->with( $this->wbTermsStoreItemId, 'de' );

		$testSubject = $this->newTestSubject();
		$testSubject->getDescription( $this->normalizedStoreItemId, 'en' );
		$testSubject->getDescription( $this->wbTermsStoreItemId, 'de' );
	}

	public function testGetLabels() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabels' )->with( $this->normalizedStoreItemId, [ 'en' ] );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getLabels' )->with( $this->wbTermsStoreItemId, [ 'de' ] );

		$testSubject = $this->newTestSubject();
		$testSubject->getLabels( $this->normalizedStoreItemId, [ 'en' ] );
		$testSubject->getLabels( $this->wbTermsStoreItemId, [ 'de' ] );
	}

	public function testGetDescriptions() {
		$this->normalizedStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescriptions' )->with( $this->normalizedStoreItemId, [ 'en' ] );

		$this->wbTermsStorePrefetchingTermLookup->expects( $this->once() )
			->method( 'getDescriptions' )->with( $this->wbTermsStoreItemId, [ 'de' ] );

		$testSubject = $this->newTestSubject();
		$testSubject->getDescriptions( $this->normalizedStoreItemId, [ 'en' ] );
		$testSubject->getDescriptions( $this->wbTermsStoreItemId, [ 'de' ] );
	}

	private function newTestSubject() {
		return new TermStoresDelegatingPrefetchingItemTermLookup(
			$this->dataAccessSettings,
			$this->normalizedStorePrefetchingTermLookup,
			$this->wbTermsStorePrefetchingTermLookup
		);
	}
}
