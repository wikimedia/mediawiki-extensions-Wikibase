<?php

namespace Wikibase\Repo\Tests\Store;

use LogicException;
use MediaWikiIntegrationTestCase;
use Onoi\MessageReporter\SpyMessageReporter;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Services\Lookup\ItemLookup;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\RevisionedUnresolvedRedirectException;
use Wikibase\Repo\Store\ItemTermsRebuilder;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Store\ItemTermsRebuilder
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemTermsRebuilderTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var ItemTermStoreWriter
	 */
	private $itemTermStoreWriter;

	/**
	 * @var SpyMessageReporter
	 */
	private $errorReporter;

	/**
	 * @var SpyMessageReporter
	 */
	private $progressReporter;

	private $itemIds;

	protected function setUp(): void {
		parent::setUp();

		$this->itemTermStoreWriter = $this->newItemTermStoreWriter();
		$this->errorReporter = new SpyMessageReporter();
		$this->progressReporter = new SpyMessageReporter();

		$this->itemIds = [
			new ItemId( 'Q1' ),
			new ItemId( 'Q2' ),
		];
	}

	public function testStoresAllTerms() {
		$this->newRebuilder()->rebuild();

		$this->assertQ1IsStored();
		$this->assertQ2IsStored();
	}

	private function newRebuilder(): ItemTermsRebuilder {
		return new ItemTermsRebuilder(
			$this->itemTermStoreWriter,
			$this->itemIds,
			$this->progressReporter,
			$this->errorReporter,
			WikibaseRepo::getRepoDomainDbFactory()->newRepoDb(),
			$this->newItemLookup(),
			1,
			0
		);
	}

	private function newItemTermStoreWriter() {
		return new class implements ItemTermStoreWriter {
			private $fingerprints = [];

			public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
				$this->fingerprints[$itemId->getNumericId()] = $terms;
			}

			public function deleteTerms( ItemId $itemId ) {
				throw new LogicException( 'Unimplemented' );
			}

			public function getTerms( ItemId $itemId ) {
				return $this->fingerprints[$itemId->getNumericId()];
			}
		};
	}

	public function assertQ1IsStored() {
		$this->assertEquals(
			$this->newQ1()->getFingerprint(),
			$this->itemTermStoreWriter->getTerms( new ItemId( 'Q1' ) )
		);
	}

	private function assertQ2IsStored() {
		$this->assertEquals(
			$this->newQ2()->getFingerprint(),
			$this->itemTermStoreWriter->getTerms( new ItemId( 'Q2' ) )
		);
	}

	private function newItemLookup(): ItemLookup {
		$lookup = new InMemoryEntityLookup();

		$lookup->addEntity( $this->newQ1() );
		$lookup->addEntity( $this->newQ2() );
		$lookup->addException(
			new RevisionedUnresolvedRedirectException( new ItemId( 'Q7251' ), new ItemId( 'Q1' ) )
		);

		return $lookup;
	}

	private function newQ1() {
		return new Item(
			new ItemId( 'Q1' ),
			new Fingerprint(
				new TermList( [
					new Term( 'en', 'EnglishItemLabel' ),
					new Term( 'de', 'GermanItemLabel' ),
					new Term( 'nl', 'DutchItemLabel' ),
				] )
			)
		);
	}

	private function newQ2() {
		return new Item(
			new ItemId( 'Q2' ),
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
			)
		);
	}

	public function testErrorsAreReported() {
		$itemTermStoreWriter = $this->createMock( ItemTermStoreWriter::class );
		$itemTermStoreWriter->expects( $this->exactly( 1 ) )
			->method( 'storeTerms' )
			->willThrowException( new TermStoreException() );
		$this->expectException( TermStoreException::class );
		$this->itemTermStoreWriter = $itemTermStoreWriter;

		$this->newRebuilder()->rebuild();
	}

	public function testProgressIsReportedEachBatch() {
		$this->newRebuilder()->rebuild();

		$this->assertSame(
			[
				'Rebuilding Q1 till Q1',
				'Rebuilding Q2 till Q2',
			],
			$this->progressReporter->getMessages()
		);
	}

	public function testNonExistentItemsDoNotCauseAnyErrors() {
		$this->itemIds[] = new ItemId( 'Q3' );
		$this->itemIds[] = new ItemId( 'Q4' );

		$this->newRebuilder()->rebuild();

		$this->assertLastMessageContains( 'Q4' );
	}

	private function assertLastMessageContains( $expectedString ) {
		$messages = $this->progressReporter->getMessages();

		$this->assertStringContainsString(
			$expectedString,
			end( $messages )
		);
	}

}
