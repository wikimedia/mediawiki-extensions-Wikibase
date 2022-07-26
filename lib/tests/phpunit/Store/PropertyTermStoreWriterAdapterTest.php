<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Term\PropertyTermStoreWriter;
use Wikibase\DataModel\Services\Term\TermStoreException;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\PropertyTermStoreWriterAdapter;

/**
 * @covers \Wikibase\Lib\Store\PropertyTermStoreWriterAdapter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriterAdapterTest extends TestCase {

	/**
	 * @var PropertyTermStoreWriter
	 */
	private $propertyTermStoreWriter;

	protected function setUp(): void {
		$this->propertyTermStoreWriter = $this->newPropertyTermStoreWriter();
	}

	public function testSaveTermsThrowsExceptionWhenGivenUnsupportedEntityType() {
		$writer = $this->newTermStoreWriter();
		$unsupportedEntity = $this->createMock( EntityDocument::class );

		$this->expectException( \InvalidArgumentException::class );
		$writer->saveTermsOfEntity( $unsupportedEntity );
	}

	private function newTermStoreWriter(): PropertyTermStoreWriterAdapter {
		return new PropertyTermStoreWriterAdapter(
			$this->propertyTermStoreWriter
		);
	}

	private function newPropertyTermStoreWriter(): PropertyTermStoreWriter {
		return new class implements PropertyTermStoreWriter {
			private $fingerprints = [];

			public function storeTerms( NumericPropertyId $propertyId, Fingerprint $terms ) {
				$this->fingerprints[$propertyId->getNumericId()] = $terms;
			}

			public function deleteTerms( NumericPropertyId $propertyId ) {
				unset( $this->fingerprints[$propertyId->getNumericId()] );
			}

			public function getTerms( PropertyId $propertyId ) {
				if ( isset( $this->fingerprints[$propertyId->getNumericId()] ) ) {
					return $this->fingerprints[$propertyId->getNumericId()];
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

	public function testSaveTermsSavesProperties() {
		$property = $this->newPropertyWithTerms();

		$this->newTermStoreWriter()->saveTermsOfEntity( $property );

		$this->assertEquals(
			$property->getFingerprint(),
			$this->propertyTermStoreWriter->getTerms( $property->getId() )
		);
	}

	private function newPropertyWithTerms(): Property {
		return new Property(
			new NumericPropertyId( 'P42' ),
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

	private function newThrowingPropertyTermStoreWriter() {
		$propertyTermStoreWriter = $this->createMock( PropertyTermStoreWriter::class );
		$propertyTermStoreWriter->method( 'storeTerms' )
			->willThrowException( new TermStoreException() );
		$propertyTermStoreWriter->method( 'deleteTerms' )
			->willThrowException( new TermStoreException() );

		return $propertyTermStoreWriter;
	}

	public function testSaveTermsReturnsFalseOnFailure() {
		$this->propertyTermStoreWriter = $this->newThrowingPropertyTermStoreWriter();

		$this->assertFalse(
			$this->newTermStoreWriter()->saveTermsOfEntity( $this->newPropertyWithTerms() )
		);
	}

	public function testDeleteTermsReturnsTrueOnSuccess() {
		$this->assertTrue(
			$this->newTermStoreWriter()->deleteTermsOfEntity( new NumericPropertyId( 'P1' ) )
		);
	}

	public function testDeleteTermsReturnsFalseOnFailure() {
		$this->propertyTermStoreWriter = $this->newThrowingPropertyTermStoreWriter();

		$this->assertFalse(
			$this->newTermStoreWriter()->deleteTermsOfEntity( new NumericPropertyId( 'P1' ) )
		);
	}

	public function testDeletesTermsDeletesPropertyTerms() {
		$property = $this->newPropertyWithTerms();

		$this->propertyTermStoreWriter->storeTerms(
			$property->getId(),
			$property->getFingerprint()
		);

		$this->newTermStoreWriter()->deleteTermsOfEntity( $property->getId() );

		$this->assertEquals(
			new Fingerprint(),
			$this->propertyTermStoreWriter->getTerms( $property->getId() )
		);
	}

}
