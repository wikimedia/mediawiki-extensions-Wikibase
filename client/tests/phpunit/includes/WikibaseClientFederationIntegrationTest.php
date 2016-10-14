<?php

namespace Wikibase\Client\Tests;

use Database;
use HashSiteStore;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\SettingsArray;

/**
 * @group Wikibase
 * @group Database
 */
class WikibaseClientFederationIntegrationTest extends MediaWikiTestCase {

	private function duplicateTableFromOtherDb( Database $db, $baseDbName, $baseTableName, $newTableName, $temporary = false ) {
		$tmp = $temporary ? 'TEMPORARY ' : '';
		$newName = $db->addIdentifierQuotes( $newTableName );
		$oldName = $db->addIdentifierQuotes( $baseDbName ) . '.' . $db->addIdentifierQuotes( $baseTableName );
		$query = "CREATE $tmp TABLE $newName (LIKE $oldName)";

		return $db->query( $query, __METHOD__ );
	}

	/**
	 * TODO: move to MediaWikiTestCase?
	 * TODO: rename to setupTestRepoDb?
	 * @param string $dbName
	 */
	protected function setupTestForeignDb( $dbName ) {
		$baseDb = wfGetDB( DB_REPLICA );

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$fooLb = $lbFactory->getMainLB( 'foowiki' );
		$tables = $baseDb->listTables();
		$baseDbName = $baseDb->getDBname();

		$db = $fooLb->getConnection( DB_MASTER, [], $dbName );

		foreach ( $tables as $table ) {
			if ( !$this->duplicateTableFromOtherDb( $db, $baseDbName, $table, $this->dbPrefix() . $table, true ) ) {
				// TODO: die loud
			}
		}
		$fooLb->reuseConnection( $db );
		$fooLb->reuseConnection( $baseDb );
	}

	public function testFoo() {
		$this->setupTestForeignDb( 'foowiki' );

		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$lbFactory->setDomainPrefix( $this->dbPrefix() );

		$fooLb = $lbFactory->getMainLB( 'foowiki' );
		$fooDbw = $fooLb->getConnection( DB_MASTER, [], 'foowiki' );

		$fooDbw->insert( 'page', [
			'page_namespace' => WB_NS_DATA,
			'page_title' => 'Q123',
		] );
		$fooPageId = $fooDbw->insertId();

		$fooDbw->insert( 'text', [
			'old_text' => '{"type":"item","id":"Q123","labels":{"en":{"language":"en","value":"Foo Item"}}' .
				',"descriptions":[],"aliases":[],"claims":[],"sitelinks":[]}',
		] );
		$fooTextId = $fooDbw->insertId();

		$fooDbw->insert( 'revision', [
			'rev_page' => $fooPageId,
			'rev_text_id' => $fooTextId,
			'rev_comment' => '/* wbeditentity-create:2|en */ Foo Item',
			'rev_user' => 0,
		] );
		$fooRevId = $fooDbw->insertId();

		$fooDbw->update(
			'page',
			[
				'page_latest' => $fooRevId,
				'page_content_model' => CONTENT_MODEL_WIKIBASE_ITEM,
			],
			[
				'page_id' => $fooPageId,
			]
		);

		$fooLb->reuseConnection( $fooDbw );

		$client = $this->getWikibaseClient();
		$lookup = $client->getRestrictedEntityLookup();

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'foo:Q123' ) ) );
		$this->assertFalse( $lookup->hasEntity( new ItemId( 'Q123' ) ) );

		// TODO: clean the temporary db
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		return new WikibaseClient(
			new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [
				'item' => [
					'entity-id-pattern' => ItemId::PATTERN,
					'entity-id-builder' => function( $serialization ) {
						return new ItemId( $serialization );
					},
				],
			] ),
			[
				'foo' => new SettingsArray( [ 'repoDatabase' => 'foowiki' ] ),
			],
			new HashSiteStore()
		);
	}

}
