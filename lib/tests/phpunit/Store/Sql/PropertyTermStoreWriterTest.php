<?php

namespace Wikibase\Lib\Tests\Store\Sql;

use MediaWiki\Logger\LoggerFactory;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\UnusableEntitySource;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
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
		$this->tablesUsed[] = 'wbt_type';
	}

	public function testSavesPropertyTermsInStore() {
		$this->fail();
	}

	public function testDoesNotIntroduceDuplicatesInStoreTables() {
		$this->fail();
	}

	public function testDeletesPropertyTermsInStore() {
		$this->fail();
	}

	public function testDoesNotDeleteRecordsInNormalizedTables() {
		$this->fail();
	}

	private function assertPropertyTermsExistInStore( Property $proeprty ) {
	}

	private function assertNoPropertyTermsExistInStore( Proeprty $proeprty ) {
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
}
