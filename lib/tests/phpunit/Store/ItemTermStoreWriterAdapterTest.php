<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Term\ItemTermStoreWriter;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\ItemTermStoreWriterAdapter;

/**
 * @covers \Wikibase\Lib\Store\ItemTermStoreWriterAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class ItemTermStoreWriterAdapterTest extends TestCase {

	/**
	 * @var ItemTermStoreWriter
	 */
	private $itemTermStoreWriter;

	protected function setUp(): void {
		$this->itemTermStoreWriter = $this->newItemTermStoreWriter();
	}

	public function testSaveTermsThrowsExceptionWhenGivenUnsupportedEntityType() {
		$writer = $this->newTermStoreWriter();
		$unsupportedEntity = $this->createMock( EntityDocument::class );

		$this->expectException( \InvalidArgumentException::class );
		$writer->saveTermsOfEntity( $unsupportedEntity );
	}

	private function newTermStoreWriter() {
		return new ItemTermStoreWriterAdapter(
			$this->itemTermStoreWriter
		);
	}

	private function newItemTermStoreWriter(): ItemTermStoreWriter {
		return new class implements ItemTermStoreWriter {
			private $fingerprints = [];

			public function storeTerms( ItemId $itemId, Fingerprint $terms ) {
				$this->fingerprints[$itemId->getNumericId()] = $terms;
			}

			public function deleteTerms( ItemId $itemId ) {
				unset( $this->fingerprints[$itemId->getNumericId()] );
			}

			public function getTerms( ItemId $itemId ) {
				if ( isset( $this->fingerprints[$itemId->getNumericId()] ) ) {
					return $this->fingerprints[$itemId->getNumericId()];
				} else {
					return new Fingerprint();
				}
			}
		};
	}

	public function testDeleteTermsThrowsExceptionWhenGivenUnsupportedEntityId() {
		$writer = $this->newTermStoreWriter();
		$unsupportedId = $this->createMock( EntityId::class );

		$this->expectException( \InvalidArgumentException::class );
		$writer->deleteTermsOfEntity( $unsupportedId );
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
			$this->itemTermStoreWriter->getTerms( $item->getId() )
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

		$this->itemTermStoreWriter->storeTerms(
			$item->getId(),
			$item->getFingerprint()
		);

		$this->newTermStoreWriter()->deleteTermsOfEntity( $item->getId() );

		$this->assertEquals(
			new Fingerprint(),
			$this->itemTermStoreWriter->getTerms( $item->getId() )
		);
	}

}
