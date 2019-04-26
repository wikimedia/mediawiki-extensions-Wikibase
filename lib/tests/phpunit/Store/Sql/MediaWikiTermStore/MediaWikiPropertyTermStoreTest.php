<?php

namespace Wikibase\Lib\Tests\Store\Sql\MediaWikiTermStore;

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\MediaWikiDatabaseAccess;
use Wikibase\Lib\Store\Sql\MediaWikiTermStore\MediaWikiPropertyTermStore;
use Wikibase\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\MediaWikiTermStore\MediaWikiPropertyTermStore
 *
 * @group Wikibase
 * @group WikibaseStore
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiPropertyTermStoreTest extends \MediaWikiTestCase {

	private $inMemorySchemaAccess;
	private $mediaWikiPropertyTermStore;

	public function setUp() {
		parent::setUp();

		$this->inMemorySchemaAccess = new InMemorySchemaAccess();
		$this->mediaWikiPropertyTermStore = new MediaWikiPropertyTermStore( $this->inMemorySchemaAccess );
	}

	public function testSavesPropertyTermsInStore() {
		$property = $this->buildPropertyWithTerms( 'p123' );

		$this->mediaWikiPropertyTermStore->storeTerms( $property->getId(), $property->getFingerprint() );

		$this->assertTrue( $this->inMemorySchemaAccess->hasPropertyTerms(
			123,
			$property->getFingerprint()
		) );
	}

	public function testDeletesPropertyTermsInStore() {
		$property = $this->buildPropertyWithTerms( 'p456' );
		$this->storePropertyTerms( $property );

		$this->mediaWikiPropertyTermStore->deleteTerms( $property->getId() );

		$this->assertTrue( $this->inMemorySchemaAccess->hasNoPropertyTerms( 123 ) );
	}

	public function testRetrievesPropertyTermsInStore() {
		$this->markTestSkipped();
	}

	/**
	 * @return Property
	 */
	private function buildPropertyWithTerms( $id ) {
		$propertyId = new PropertyId( $id );

		$labels = new TermList( [
			new Term( 'en', 'hello' ),
			new Term( 'es', 'hola' )
		] );
		$descriptions = new TermList( [
			new Term( 'en', 'a greeting' ),
			new Term( 'es', 'un saludo' )
		] );
		$aliasGroups = new AliasGroupList( [
			new AliasGroup( 'en', [ 'hi', 'hey' ] ),
			new AliasGroup( 'es', [ 'saludo', 'hey' ] )
		] );
		$fingerprint = new Fingerprint( $labels, $descriptions, $aliasGroups );

		return new Property( $propertyId, $fingerprint, 'datatype' );
	}

	private function storePropertyTerms( Property $property ) {
		$this->inMemorySchemaAccess->setPropertyTermsFromFingerprint(
			$property->getId()->getNumericId(),
			$property->getFingerprint()
		);
	}

}
