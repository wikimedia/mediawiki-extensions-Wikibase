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
		$this->populateWbtTypeTable();
	}

	private function populateWbtTypeTable() {
		$this->db->insert('wbt_type', [ 'wby_name' => 'label' ]);
		$this->db->insert('wbt_type', [ 'wby_name' => 'description' ]);
		$this->db->insert('wbt_type', [ 'wby_name' => 'alias' ]);
	}

	public function testDoesNotAcceptEntitiesThatAreNotProperties() {
		$this->expectException(invalidargumentexception::class);
		$writer = $this->getPropertyTermStoreWriter();

		$writer->saveTerms( new item() );
	}

	public function testSavesPropertyTermsInStore() {
		$property = $this->buildPropertyWithTerms( 'p123' );
		$writer = $this->getPropertyTermStoreWriter();

		// $this->assertNoPropertyTermsExistInStore( 123, $property );

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
			$textIds[] = $textRow->wbxl_id;
		}

		// check text in lang records
		$textInLangRows = $this->db->select(
			'wbt_text_in_lang',
			[ 'wbxl_id' ],
			[ 'wbxl_text_id' => $textIds, 'wbxl_language' => $langs ]
		);

		$this->assertEquals( count( $texts ), $textInLangIDs->numRows() );

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
			[ 'wbpt_property_id' => $propertyIdIntPart, 'wbpt_term_in_lang_id' => $termInLangIds ]
		);

		$this->assertEquals( count( $texts ), $propertyTermsCount );
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
			new AliasGroup( 'es', [ 'saludo', 'hey' ] )
		] );
		$fingerprint = new Fingerprint( $labels, $descriptions, $aliasGroups );

		return new Property( $propertyId, $fingerprint, 'datatype' );
	}
}
