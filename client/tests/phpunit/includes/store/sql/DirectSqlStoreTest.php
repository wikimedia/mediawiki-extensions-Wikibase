<?php

namespace Wikibase\Test;

use Language;
use MediaWikiSite;
use Site;
use Wikibase\Client\WikibaseClient;
use Wikibase\DirectSqlStore;

/**
 * @covers Wikibase\DirectSqlStore
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @licence GNU GPL v2+
 * @author DanielKinzler
 */
class DirectSqlStoreTest extends \MediaWikiTestCase {

	protected function newStore() {
		$site = new Site( MediaWikiSite::TYPE_MEDIAWIKI );
		$site->setGlobalId( 'dummy' );
		$lang = Language::factory( 'en' );

		$contentCodec = WikibaseClient::getDefaultInstance()->getEntityContentDataCodec();
		$entityFactory = WikibaseClient::getDefaultInstance()->getEntityFactory();

		$store = new DirectSqlStore( $contentCodec, $entityFactory, $lang, 'DirectStoreSqlTestDummyRepoId');
		$store->setSite( $site ); //TODO: inject via constructor once that is possible

		return $store;
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType ) {
		$store = $this->newStore();

		$obj = $store->$getter();

		$this->assertInstanceOf( $expectedType, $obj );
	}

	public static function provideGetters() {
		return array(
			array( 'getItemUsageIndex', 'Wikibase\ItemUsageIndex' ),
			array( 'getSiteLinkTable', 'Wikibase\Lib\Store\SiteLinkTable' ),
			array( 'getEntityLookup', 'Wikibase\Lib\Store\EntityLookup' ),
			array( 'getTermIndex', 'Wikibase\TermIndex' ),
			array( 'getPropertyLabelResolver', 'Wikibase\PropertyLabelResolver' ),
			array( 'newChangesTable', 'Wikibase\ChangesTable' ),
			array( 'getPropertyInfoStore', 'Wikibase\PropertyInfoStore' ),
		);
	}

}
