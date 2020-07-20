<?php

namespace Wikibase\Repo\Tests\Store;

use LogicException;
use MediaWiki\MediaWikiServices;
use MediaWikiIntegrationTestCase;
use Onoi\MessageReporter\SpyMessageReporter;
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
			MediaWikiServices::getInstance()->getDBLoadBalancerFactory(),
			$this->newPropertyLookup(),
			1,
			0
		);
	}

	private function newPropertyTermStoreWriter() {
		return new class implements PropertyTermStoreWriter {
			private $fingerprints = [];

			public function storeTerms( PropertyId $propertyId, Fingerprint $terms ) {
				$this->fingerprints[$propertyId->getNumericId()] = $terms;
			}

			public function deleteTerms( PropertyId $propertyId ) {
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
			$this->propertyTermStoreWriter->getTerms( new PropertyId( 'P1' ) )
		);
	}

	private function assertP2IsStored() {
		$this->assertEquals(
			$this->newP2()->getFingerprint(),
			$this->propertyTermStoreWriter->getTerms( new PropertyId( 'P2' ) )
		);
	}

	private function newIdPager(): InMemoryEntityIdPager {
		return new InMemoryEntityIdPager(
			new PropertyId( 'P1' ),
			new PropertyId( 'P2' )
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
			new PropertyId( 'P1' ),
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
			new PropertyId( 'P2' ),
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
		$propertyTermStoreWriter->expects( $this->exactly( 2 ) )
			->method( 'storeTerms' )
			->will( $this->throwException( new TermStoreException() ) );
		$this->propertyTermStoreWriter = $propertyTermStoreWriter;

		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Failed to save terms of property: P1',
				'Failed to save terms of property: P2',
			],
			$this->errorReporter->getMessages()
		);
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
