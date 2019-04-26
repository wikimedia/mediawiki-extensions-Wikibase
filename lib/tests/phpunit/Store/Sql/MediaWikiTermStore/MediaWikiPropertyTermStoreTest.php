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
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class MediaWikiPropertyTermStoreTest extends \MediaWikiTestCase {
	
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

	public function testSavesPropertyTermsInStore() {
		$property = $this->buildPropertyWithTerms( 'p123' );
		$store = $this->getPropertyTermStore();

		$store->storeTerms( $property->getId(), $property->getFingerprint() );

		$this->assertPropertyTermsExistInStore( 123, $property );
	}

	public function testDeletesPropertyTermsInStore() {
		$property = $this->buildPropertyWithTerms( 'p456' );
		$this->storePropertyTerms( $property );
		$store = $this->getPropertyTermStore();
		
		$store->deleteTerms( $property->getId() );

		$this->assertNoPropertyTermsExistInStore( 456 );
	}

	public function testDoesNotDeleteRecordsInNormalizedTables() {
		$this->markTestSkipped();
	}

	private function assertPropertyTermsExistInStore( int $propertyIdIntPart, Property $property ) {
		$texts = [];
		$langs = [];
		foreach ( $property->getLabels() as $lang => $label) {
			$texts[] = $label->getText();
			$langs[] = $label->getLanguageCode();
		}
		foreach ( $property->getDescriptions() as $lang => $description) {
			$texts[] = $description->getText();
			$langs[] = $description->getLanguageCode();
		}
		foreach ( $property->getAliasGroups() as $aliasGroup ) {
			$texts = array_merge(
				$texts,
				$aliasGroup->getAliases()
			);
			$langs[] = $aliasGroup->getLanguageCode();
		}
		$uniqueTexts = array_unique( $texts );

		// check text recrods
		$textRows = $this->db->select(
			'wbt_text',
			'wbx_id',
			[ 'wbx_text' => $uniqueTexts ]
		);

		$this->assertEquals( count($uniqueTexts), $textRows->numRows() );

		$textIds = [];
		foreach( $textRows as $textRow ) {
			$textIds[] = $textRow->wbx_id;
		}

		// check text in lang records
		$textInLangRows = $this->db->select(
			'wbt_text_in_lang',
			[ 'wbxl_id' ],
			[ 'wbxl_text_id' => $textIds, 'wbxl_language' => $langs ]
		);

		$this->assertEquals( count( $texts ), $textInLangRows->numRows() );

		$textInLangIds = [];
		foreach( $textInLangRows as $textInLangRow ) {
			$textInLangIds[] = $textInLangRow->wbxl_id;
		}

		// check term in lang records
		$termInLangRows = $this->db->select(
			'wbt_term_in_lang',
			[ 'wbtl_id' ],
			[ 'wbtl_text_in_lang_id' => $textInLangIds ]
		);

		$this->assertEquals( count( $texts ), $termInLangRows->numRows() );

		$termInLangIds = [];
		foreach( $termInLangRows as $termInLangRow ) {
			$termInLangIds[] = $termInLangRow->wbtl_id;
		}

		// assert property terms records
		$propertyTermsCount = $this->db->selectRowCount(
			'wbt_property_terms',
			[ '*' ],
			[ 'wbpt_property_id' => $propertyIdIntPart, 'wbpt_term_in_lang_id' => $termInLangIds ]
		);

		$this->assertEquals( count( $texts ), $propertyTermsCount );
	}

	private function assertNoPropertyTermsExistInStore( int $propertyIdIntPart ) {
		$propertyTermsRowsCount = $this->db->selectRowCount(
			'wbt_property_terms',
			[ '*' ],
			[ 'wbpt_property_id' => $propertyIdIntPart ]
		);
		$this->assertEquals( 0, $propertyTermsRowsCount );
	}

	/**
	 * @return PropertyTermStoreWriter
	 */
	private function getPropertyTermStore() {
		return new MediaWikiPropertyTermStore(
			new MediaWikiDatabaseAccess( $this->db )
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
			new AliasGroup( 'es', [ 'saludo', 'hey' ] )
		] );
		$fingerprint = new Fingerprint( $labels, $descriptions, $aliasGroups );

		return new Property( $propertyId, $fingerprint, 'datatype' );
	}

	private function storePropertyTerms( Property $property ) {
		// DOING: either continue with this approach or make an in-memory implementation of
		// SchemaAccess to isolate this test from DB
	}
	
}
