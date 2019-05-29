<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use PHPUnit4And6Compat;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\TermIndex;
use Wikibase\TermIndexEntry;
use Wikibase\TermIndexPropertyTermStore;

/**
 * @covers \Wikibase\TermIndexPropertyTermStore
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexPropertyTermStoreTest extends TestCase {

	use PHPUnit4And6Compat;

	/** @var PropertyId */
	private $propertyId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() {
		parent::setUp();
		$this->propertyId = new PropertyId( 'P1' );
		$this->fingerprint = new Fingerprint(
			new TermList( [ new Term( 'en', 'a label' ) ] ),
			new TermList( [ new Term( 'en', 'a description' ) ] ),
			new AliasGroupList( [
				new AliasGroup( 'en', [ 'an alias', 'another alias' ] )
			] )
		);
	}

	public function testStoreTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'saveTermsOfEntity' )
			->with( $this->callback(
				function ( Property $property ) {
					$this->assertSame( $this->propertyId, $property->getId() );
					$this->assertSame( $this->fingerprint, $property->getFingerprint() );
					return true;
				}
			) );
		$propertyTermStore = new TermIndexPropertyTermStore( $termIndex );

		$propertyTermStore->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testDeleteTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->propertyId );
		$propertyTermStore = new TermIndexPropertyTermStore( $termIndex );

		$propertyTermStore->deleteTerms( $this->propertyId );
	}

	public function testGetTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'getTermsOfEntity' )
			->with(
				$this->propertyId,
				[
					TermIndexEntry::TYPE_LABEL,
					TermIndexEntry::TYPE_DESCRIPTION,
					TermIndexEntry::TYPE_ALIAS,
				]
			)
			->willReturn( [
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_LABEL,
					'termLanguage' => 'en',
					'termText' => 'a label',
					'entityId' => $this->propertyId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_DESCRIPTION,
					'termLanguage' => 'en',
					'termText' => 'a description',
					'entityId' => $this->propertyId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_ALIAS,
					'termLanguage' => 'en',
					'termText' => 'an alias',
					'entityId' => $this->propertyId,
				] ),
				new TermIndexEntry( [
					'termType' => TermIndexEntry::TYPE_ALIAS,
					'termLanguage' => 'en',
					'termText' => 'another alias',
					'entityId' => $this->propertyId,
				] ),
			] );
		$propertyTermStore = new TermIndexPropertyTermStore( $termIndex );

		$fingerprint = $propertyTermStore->getTerms( $this->propertyId );

		$this->assertEquals( $this->fingerprint, $fingerprint );
	}

}
