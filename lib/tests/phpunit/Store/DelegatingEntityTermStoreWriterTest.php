<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\DelegatingEntityTermStoreWriter;
use Wikibase\TermStore\Implementations\InMemoryItemTermStore;
use Wikibase\TermStore\Implementations\InMemoryPropertyTermStore;
use Wikibase\TermStore\Implementations\ThrowingPropertyTermStore;

/**
 * @covers \Wikibase\Lib\Store\DelegatingEntityTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class DelegatingEntityTermStoreWriterTest extends TestCase {
	use PHPUnit4And6Compat;

	/**
	 * @var InMemoryPropertyTermStore
	 */
	private $propertyTermStore;

	/**
	 * @var InMemoryItemTermStore
	 */
	private $itemTermStore;

	public function setUp() {
		$this->propertyTermStore = new InMemoryPropertyTermStore();
		$this->itemTermStore = new InMemoryItemTermStore();
	}

	public function testSaveTermsThrowsExceptionWhenGivenUnsupportedEntityType() {
		$writer = $this->newTermStoreWriter();

		$this->expectException( \InvalidArgumentException::class );
		$writer->saveTermsOfEntity( $this->newUnsupportedEntity() );
	}

	private function newTermStoreWriter() {
		return new DelegatingEntityTermStoreWriter(
			$this->propertyTermStore,
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

	public function testSaveTermsSavesProperties() {
		$property = $this->newPropertyWithTerms();

		$this->newTermStoreWriter()->saveTermsOfEntity( $property );

		$this->assertEquals(
			$property->getFingerprint(),
			$this->propertyTermStore->getTerms( $property->getId() )
		);
	}

	private function newPropertyWithTerms(): Property {
		return new Property(
			new PropertyId( 'P42' ),
			$this->newFingerprint(),
			'irrelevant-datatype'
		);
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

	public function testSaveTermsReturnsTrueOnSuccess() {
		$this->assertTrue(
			$this->newTermStoreWriter()->saveTermsOfEntity( $this->newPropertyWithTerms() )
		);
	}

	public function testSaveTermsReturnsFalseOnFailure() {
		$this->propertyTermStore = new ThrowingPropertyTermStore();

		$this->assertFalse(
			$this->newTermStoreWriter()->saveTermsOfEntity( $this->newPropertyWithTerms() )
		);
	}

	public function testDeleteTermsReturnsTrueOnSuccess() {
		$this->assertTrue(
			$this->newTermStoreWriter()->deleteTermsOfEntity( new PropertyId( 'P1' ) )
		);
	}

	public function testDeleteTermsReturnsFalseOnFailure() {
		$this->propertyTermStore = new ThrowingPropertyTermStore();

		$this->assertFalse(
			$this->newTermStoreWriter()->deleteTermsOfEntity( new PropertyId( 'P1' ) )
		);
	}

	public function testDeletesTermsDeletesPropertyTerms() {
		$property = $this->newPropertyWithTerms();

		$this->propertyTermStore->storeTerms(
			$property->getId(),
			$property->getFingerprint()
		);

		$this->newTermStoreWriter()->deleteTermsOfEntity( $property->getId() );

		$this->assertEquals(
			new Fingerprint(),
			$this->propertyTermStore->getTerms( $property->getId() )
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
