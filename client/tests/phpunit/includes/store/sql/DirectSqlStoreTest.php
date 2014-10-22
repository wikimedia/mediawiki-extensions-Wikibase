<?php

namespace Wikibase\Test;

use Language;
use MediaWikiSite;
use Site;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
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
		$idParser = new BasicEntityIdParser();

		$contentCodec = WikibaseClient::getDefaultInstance()->getEntityContentDataCodec();

		$store = new DirectSqlStore( $contentCodec, $lang, $idParser, 'DirectStoreSqlTestDummyRepoId');
		$store->setSite( $site ); //TODO: inject via constructor once that is possible

		return $store;
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType ) {
		$store = $this->newStore();

		$this->assertTrue( method_exists( $store, $getter ), "Method $getter" );

		$obj = $store->$getter();

		$this->assertInstanceOf( $expectedType, $obj );
	}

	public static function provideGetters() {
		return array(
			array( 'getSiteLinkTable', 'Wikibase\Lib\Store\SiteLinkTable' ),
			array( 'getEntityLookup', 'Wikibase\Lib\Store\EntityLookup' ),
			array( 'getTermIndex', 'Wikibase\TermIndex' ),
			array( 'getPropertyLabelResolver', 'Wikibase\PropertyLabelResolver' ),
			array( 'newChangesTable', 'Wikibase\ChangesTable' ),
			array( 'getPropertyInfoStore', 'Wikibase\PropertyInfoStore' ),
			array( 'getItemUsageIndex', 'Wikibase\ItemUsageIndex' ),
			array( 'getUsageTracker', 'Wikibase\Client\Usage\UsageTracker' ),
			array( 'getUsageLookup', 'Wikibase\Client\Usage\UsageLookup' ),
			array( 'getSubscriptionManager', 'Wikibase\Client\Usage\SubscriptionManager' ),
		);
	}

}
