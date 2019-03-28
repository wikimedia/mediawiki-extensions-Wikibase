<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use InvalidArgumentException;
use MediaWiki\Logger\LoggerFactory;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Term\AliasGroup;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\Fingerprint;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lib\Store\Sql\PropertyTermStoreWriter;
use Wikibase\WikibaseSettings;

/**
 * @covers \Wikibase\Lib\Store\Sql\PropertyTermStoreWriter
 *
 * @group Wikibase
 * @group WikibaseStore
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class PropertyTermStoreWriterTest extends \MediaWikiTestCase {
	protected function setUp() {
		parent::setUp();

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$this->markTestSkipped( "Skipping because a local wbt_* tables"
									. " are not available on a WikibaseClient only instance." );
		}

		$this->tablesUsed[] = 'wbt_property_terms';
		$this->tablesUsed[] = 'wbt_term_in_lang';
		$this->tablesUsed[] = 'wbt_text';
		$this->tablesUsed[] = 'wbt_text_in_lang';
	}

	public function beforeTest() {
		$this->populateWbtTypeTable();
	}

	private function populateWbtTypeTable() {
		$this->db->insert('wbt_type', [ 'wby_name' => 'label' ]);
		$this->db->insert('wbt_type', [ 'wby_name' => 'description' ]);
		$this->db->insert('wbt_type', [ 'wby_name' => 'alias' ]);
	}

	public function testDoesNotAcceptEntitiesThatAreNotProperties() {
		$this->expectexception(invalidargumentexception::class);
		$writer = $this->getpropertytermstorewriter();

		$writer->saveterms( new item() );
	}

	public function testSavesPropertyTermsInStore() {
		$property = $this->buildpropertywithterms( 'p123' );
		$writer = $this->getpropertytermstorewriter();

		// $this->assertnopropertytermsexistinstore( 123, $property );

		$writer->saveTerms( $property );

		$this->assertPropertyTermsExistInStore( 123, $property );
	}

	public function testDoesNotIntroduceDuplicatesInStoreTables() {
		$this->fail( 'not implemented' );
	}

	public function testDeletesPropertyTermsInStore() {
		$this->fail( 'not implemented' );
	}

	public function testDoesNotDeleteRecordsInNormalizedTables() {
		$this->fail( 'not implemented' );
	}

	private function assertPropertyTermsExistInStore( int $propertyIdIntPart, Property $property ) {
		$labels = $property->getLabels();
		$descriptions = $property->getDescriptions();
		$aliasGroups = $property->getAliasGroups();

		// assert text records exist, and no duplicates
		$termTexts = array_merge(
			$labels->toTextArray(),
			$descriptions->toTextArray()
		);
		foreach ( $aliasGroups as $aliasGroup ) {
			$termTexts = array_merge(
				$termTexts,
				$aliasGroup->getAliases()
			);
		}
		foreach ( $termTexts as $termText ) {
			$this->assertSelect( 'wbt_text', [ 'wbx_text' ], [ 'wbx_text' => $termText ], [ $termText ] );
		}

		// assert text in lang records
		// assert term in lang records
		// assert property terms records
	}

	private function assertNoPropertyTermsExistInStore( int $propertyIdIntPart, Property $proeprty ) {
		$this->fail( 'not implemented' );
	}

	/**
	 * @return PropertyTermStoreWriter
	 */
	private function getPropertyTermStoreWriter() {
		return new PropertyTermStoreWriter(
			new UnusableEntitySource(),
			new DataAccessSettings( 100, false, false, DataAccessSettings::USE_REPOSITORY_PREFIX_BASED_FEDERATION ),
			LoggerFactory::getInstance( 'Wikibase' )
		);
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
			new AliasGroup( 'es', [ 'saludo' ] )
		] );
		$fingerprint = new Fingerprint( $labels, $descriptions, $aliasGroups );

		return new Property( $propertyId, $fingerprint, 'datatype' );
	}
}
