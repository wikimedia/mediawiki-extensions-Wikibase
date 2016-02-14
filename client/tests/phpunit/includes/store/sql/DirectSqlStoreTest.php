<?php

namespace Wikibase\Client\Tests\Store\Sql;

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
		$idParser = new BasicEntityIdParser();

		$contentCodec = WikibaseClient::getDefaultInstance()->getEntityContentDataCodec();

		$store = new DirectSqlStore( $contentCodec, $idParser, wfWikiID(), 'en' );

		return $store;
	}

	/**
	 * @dataProvider provideGetters
	 */
	public function testGetters( $getter, $expectedType, $needsLocalRepo = false ) {
		if ( $needsLocalRepo && !defined( 'WB_VERSION' ) ) {
			$this->markTestSkipped( $getter . ' needs the repository extension to be active.' );
		}

		$store = $this->newStore();

		$this->assertTrue( method_exists( $store, $getter ), "Method $getter" );

		$obj = $store->$getter();

		$this->assertInstanceOf( $expectedType, $obj );
	}

	public function provideGetters() {
		return array(
			array( 'getSiteLinkLookup', 'Wikibase\Lib\Store\SiteLinkLookup' ),
			array( 'getEntityLookup', 'Wikibase\DataModel\Services\Lookup\EntityLookup' ),
			array( 'getTermIndex', 'Wikibase\TermIndex' ),
			array( 'getPropertyLabelResolver', 'Wikibase\DataModel\Services\Term\PropertyLabelResolver' ),
			array( 'getPropertyInfoStore', 'Wikibase\PropertyInfoStore' ),
			array( 'getUsageTracker', 'Wikibase\Client\Usage\UsageTracker' ),
			array( 'getUsageLookup', 'Wikibase\Client\Usage\UsageLookup' ),
			array( 'getSubscriptionManager', 'Wikibase\Client\Usage\SubscriptionManager', true ),
			array( 'getEntityIdLookup', 'Wikibase\Store\EntityIdLookup' ),
			array( 'getEntityPrefetcher', 'Wikibase\DataModel\Services\Entity\EntityPrefetcher' ),
			array( 'getChangeLookup', 'Wikibase\Lib\Store\ChangeLookup' ),
			array( 'getRecentChangesDuplicateDetector', 'Wikibase\Client\RecentChanges\RecentChangesDuplicateDetector' ),
		);
	}

}
