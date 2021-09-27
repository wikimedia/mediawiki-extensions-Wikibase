<?php

namespace Wikibase\Repo\Tests\Store;

use LogicException;
use MediaWikiIntegrationTestCase;
use Onoi\MessageReporter\SpyMessageReporter;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\InMemoryEntityIdPager;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Repo\Store\PropertyTermsRebuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\PropertyTermsRebuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermsRebuilderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var PropertyTermStoreWriter
	 */
	private $propertyTermStoreWriter;

	/**
	 * @var SpyMessageReporter
	 */
	private $errorReporter;

	/**
	 * @var SpyMessageReporter
	 */
	private $progressReporter;

	protected function setUp(): void {
		parent::setUp();

		$this->propertyTermStoreWriter = $this->newPropertyTermStoreWriter();
		$this->errorReporter = new SpyMessageReporter();
		$this->progressReporter = new SpyMessageReporter();
	}

	public function testStoresAllTerms() {
		$this->newRebuilder()->rebuild();

		$this->assertP1IsStored();
		$this->assertP2IsStored();
	}

	private function newRebuilder(): PropertyTermsRebuilder {
		return new PropertyTermsRebuilder(
			$this->propertyTermStoreWriter,
			$this->newIdPager(),
			$this->progressReporter,
			$this->errorReporter,
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb(),
			$this->newPropertyLookup(),
			1,
			0
		);
	}

	private function newPropertyTermStoreWriter() {
		return new class implements PropertyTermStoreWriter {
			private $fingerprints = [];

			public function storeTerms( NumericPropertyId $propertyId, Fingerprint $terms ) {
				$this->fingerprints[$propertyId->getNumericId()] = $terms;
			}

			public function deleteTerms( NumericPropertyId $propertyId ) {
				throw new LogicException( 'Unimplemented' );
			}

			public function getTerms( PropertyId $propertyId ) {
				return $this->fingerprints[$propertyId->getNumericId()];
			}
		};
	}

	public function assertP1IsStored() {
		$this->assertEquals(
			$this->newP1()->getFingerprint(),
			$this->propertyTermStoreWriter->getTerms( new NumericPropertyId( 'P1' ) )
		);
	}

	private function assertP2IsStored() {
		$this->assertEquals(
			$this->newP2()->getFingerprint(),
			$this->propertyTermStoreWriter->getTerms( new NumericPropertyId( 'P2' ) )
		);
	}

	private function newIdPager(): InMemoryEntityIdPager {
		return new InMemoryEntityIdPager(
			new NumericPropertyId( 'P1' ),
			new NumericPropertyId( 'P2' )
		);
	}

	private function newPropertyLookup(): PropertyLookup {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( $this->newP1() );
		$lookup->addEntity( $this->newP2() );

		return $lookup;
	}

	private function newP1() {
		return new Property(
			new NumericPropertyId( 'P1' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishPropLabel' ),
					new Term( 'de', 'GermanPropLabel' ),
					new Term( 'nl', 'DutchPropLabel' ),
				] )
			),
			'data-type-id'
		);
	}

	private function newP2() {
		return new Property(
			new NumericPropertyId( 'P2' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishLabel' ),
					new Term( 'de', 'ZeGermanLabel' ),
					new Term( 'fr', 'LeFrenchLabel' ),
				] ),
				new TermList( [
					new Term( 'en', 'EnglishDescription' ),
					new Term( 'de', 'ZeGermanDescription' ),
				] ),
				new AliasGroupList( [
					new AliasGroup( 'fr', [ 'LeFrenchAlias', 'LaFrenchAlias' ] ),
					new AliasGroup( 'en', [ 'EnglishAlias' ] ),
				] )
			),
			'data-type-id'
		);
	}

	public function testErrorsAreReported() {
		$propertyTermStoreWriter = $this->createMock( PropertyTermStoreWriter::class );
		$propertyTermStoreWriter->expects( $this->exactly( 1 ) )
			->method( 'storeTerms' )
			->willThrowException( new TermStoreException() );
		$this->propertyTermStoreWriter = $propertyTermStoreWriter;
		$this->expectException( TermStoreException::class );

		$this->newRebuilder()->rebuild();
	}

	public function testProgressIsReportedEachBatch() {
		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Processed up to page 1 (P1)',
				'Processed up to page 2 (P2)',
			],
			$this->progressReporter->getMessages()
		);
	}

}
