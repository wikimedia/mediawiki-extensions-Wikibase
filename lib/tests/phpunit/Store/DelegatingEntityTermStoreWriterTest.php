<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\DelegatingEntityTermStoreWriter;
use Wikibase\TermStore\PropertyTermStore;
use Wikibase\TermStore\TermStoreException;

/**
 * @covers \Wikibase\Lib\Store\DelegatingEntityTermStoreWriter
 *
 * @license GPL-2.0-or-later
 */
class DelegatingEntityTermStoreWriterTest extends TestCase {

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $propertyTermStore;

	public function setUp() {
		$this->propertyTermStore = $this->createMock( PropertyTermStore::class );
	}

	public function testSaveTermsThrowsExceptionWhenGivenUnsupportedEntityType() {
		$writer = $this->newTermStoreWriter();

		$this->expectException( \InvalidArgumentException::class );
		$writer->saveTerms( $this->newUnsupportedEntity() );
	}

	private function newTermStoreWriter() {
		return new DelegatingEntityTermStoreWriter( $this->propertyTermStore );
	}

	private function newUnsupportedEntity() {
		return $this->createMock( EntityDocument::class );
	}

	public function testDeleteTermsThrowsExceptionWhenGivenUnsupportedEntityId() {
		$writer = $this->newTermStoreWriter();

		$this->expectException( \InvalidArgumentException::class );
		$writer->deleteTerms( $this->newUnsupportedId() );
	}

	public function newUnsupportedId() {
		return $this->createMock( EntityId::class );
	}

	public function testSaveTermsSavesProperties() {
		$property = $this->newPropertyWithTerms();

		$this->expectPropertyToBeStored( $property );

		$this->newTermStoreWriter()->saveTerms( $property );
	}

	private function newPropertyWithTerms(): Property {
		return new Property(
			new PropertyId( 'P42' ),
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
			'irrelevant-datatype'
		);
	}

	private function expectPropertyToBeStored( Property $property ) {
		$this->propertyTermStore->expects( $this->once() )
			->method( 'storeTerms' )
			->with(
				$this->equalTo( $property->getId() ),
				$this->equalTo( $property->getFingerprint() )
			);
	}

	public function testSaveTermsReturnsTrueOnSuccess() {
		$this->assertTrue(
			$this->newTermStoreWriter()->saveTerms( $this->newPropertyWithTerms() )
		);
	}

	public function testSaveTermsReturnsFalseOnFailure() {
		$this->makePropertyTermStoreThrowException();

		$this->assertFalse(
			$this->newTermStoreWriter()->saveTerms( $this->newPropertyWithTerms() )
		);
	}

	private function makePropertyTermStoreThrowException() {
		$this->propertyTermStore->expects( $this->any() )
			->method( $this->anything() )
			->willThrowException( new TermStoreException() );
	}

	public function testDeleteTermsReturnsTrueOnSuccess() {
		$this->assertTrue(
			$this->newTermStoreWriter()->deleteTerms( new PropertyId( 'P1' ) )
		);
	}

	public function testDeleteTermsReturnsFalseOnFailure() {
		$this->makePropertyTermStoreThrowException();

		$this->assertFalse(
			$this->newTermStoreWriter()->deleteTerms( new PropertyId( 'P1' ) )
		);
	}

	public function testDeletesTermsDeletesPropertyTerms() {
		$propertyId = new PropertyId( 'P1' );

		$this->propertyTermStore->expects( $this->once() )
			->method( 'deleteTerms' )
			->with(
				$this->equalTo( $propertyId )
			);

		$this->newTermStoreWriter()->deleteTerms( $propertyId );
	}

}
