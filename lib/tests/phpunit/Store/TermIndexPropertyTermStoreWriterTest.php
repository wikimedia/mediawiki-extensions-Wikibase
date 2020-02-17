<?php

namespace Wikibase\Lib\Tests\Store;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\TermIndex;
use Wikibase\Lib\Store\TermIndexPropertyTermStoreWriter;

/**
 * @covers \Wikibase\Lib\Store\TermIndexPropertyTermStoreWriter
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class TermIndexPropertyTermStoreWriterTest extends TestCase {

	/** @var PropertyId */
	private $propertyId;

	/** @var Fingerprint */
	private $fingerprint;

	protected function setUp() : void {
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
		$propertyTermStoreWriter = new TermIndexPropertyTermStoreWriter( $termIndex );

		$propertyTermStoreWriter->storeTerms( $this->propertyId, $this->fingerprint );
	}

	public function testDeleteTerms() {
		$termIndex = $this->createMock( TermIndex::class );
		$termIndex->expects( $this->once() )
			->method( 'deleteTermsOfEntity' )
			->with( $this->propertyId );
		$propertyTermStoreWriter = new TermIndexPropertyTermStoreWriter( $termIndex );

		$propertyTermStoreWriter->deleteTerms( $this->propertyId );
	}

}
