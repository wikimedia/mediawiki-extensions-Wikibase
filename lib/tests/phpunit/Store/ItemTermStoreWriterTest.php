<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\ItemTermStoreWriter;
use Wikibase\TermStore\Implementations\InMemoryItemTermStore;

/**
 * @covers \Wikibase\Lib\Store\ItemTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemTermStoreWriterTest extends TestCase {

	/**
	 * @var InMemoryItemTermStore
	 */
	private $itemTermStore;

	public function setUp() : void {
		$this->itemTermStore = new InMemoryItemTermStore();
	}

	public function testSaveTermsThrowsExceptionWhenGivenUnsupportedEntityType() {
		$writer = $this->newTermStoreWriter();

		$this->expectException( \InvalidArgumentException::class );
		$writer->saveTermsOfEntity( $this->newUnsupportedEntity() );
	}

	private function newTermStoreWriter() {
		return new ItemTermStoreWriter(
			$this->itemTermStore
		);
	}

	private function newUnsupportedEntity() {
		return $this->createMock( EntityDocument::class );
	}

	public function testDeleteTermsThrowsExceptionWhenGivenUnsupportedEntityId() {
		$writer = $this->newTermStoreWriter();

		$this->expectException( \InvalidArgumentException::class );
		$writer->deleteTermsOfEntity( $this->newUnsupportedId() );
	}

	public function newUnsupportedId() {
		return $this->createMock( EntityId::class );
	}

	private function newFingerprint(): Fingerprint {
		return new Fingerprint(
			new TermList(
				[
					new Term( 'en', 'EnglishLabel' ),
					new Term( 'de', 'ZeGermanLabel' ),
					new Term( 'fr', 'LeFrenchLabel' ),
				]
			),
			new TermList(
				[
					new Term( 'en', 'EnglishDescription' ),
					new Term( 'de', 'ZeGermanDescription' ),
				]
			),
			new AliasGroupList(
				[
					new AliasGroup( 'fr', [ 'LeFrenchAlias', 'LaFrenchAlias' ] ),
					new AliasGroup( 'en', [ 'EnglishAlias' ] ),
				]
			)
		);
	}

	public function testSaveTermsSavesItems() {
		$item = $this->newItemWithTerms();

		$this->newTermStoreWriter()->saveTermsOfEntity( $item );

		$this->assertEquals(
			$item->getFingerprint(),
			$this->itemTermStore->getTerms( $item->getId() )
		);
	}

	private function newItemWithTerms(): Item {
		return new Item(
			new ItemId( 'Q42' ),
			$this->newFingerprint()
		);
	}

	public function testDeletesTermsDeletesItemTerms() {
		$item = $this->newItemWithTerms();

		$this->itemTermStore->storeTerms(
			$item->getId(),
			$item->getFingerprint()
		);

		$this->newTermStoreWriter()->deleteTermsOfEntity( $item->getId() );

		$this->assertEquals(
			new Fingerprint(),
			$this->itemTermStore->getTerms( $item->getId() )
		);
	}

}
