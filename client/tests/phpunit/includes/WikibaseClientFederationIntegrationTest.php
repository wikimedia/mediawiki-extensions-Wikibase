<?php

namespace Wikibase\Client\Tests;

use HashSiteStore;
use Language;
use MediaWiki\MediaWikiServices;
use MediaWikiTestCase;
use Revision;
use Title;
use TitleValue;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\SettingsArray;
use WikiPage;

class WikibaseClientFederationIntegrationTest extends MediaWikiTestCase {

	public function testFoo() {
		$lbFactory = MediaWikiServices::getInstance()->getDBLoadBalancerFactory();
		$localDbw = $lbFactory->getMainLB()->getConnection( DB_MASTER );
		$fooLb = $lbFactory->getMainLB( 'foowiki' );

		$fooDbw = $fooLb->getConnection( DB_MASTER );

		$fooItemTitle = Title::newFromLinkTarget( new TitleValue( WB_NS_DATA, 'Q123' ) );
		$fooItemPage = WikiPage::factory( $fooItemTitle );
		$fooItemPageId = $fooItemPage->insertOn( $fooDbw );

		$fooItemRevision = new Revision( [
			'page'       => $fooItemPageId,
			'title'      => $fooItemTitle,
			'comment'    => '/* wbeditentity-create:2|en */ Foo Item',
			'text' => '{"type":"item","id":"Q123","labels":{"en":{"language":"en","value":"Foo Item"}},"descriptions":[],"aliases":[],"claims":[],"sitelinks":[]}',
			'content_model' => CONTENT_MODEL_WIKIBASE_ITEM,
		] );
		$fooItemRevision->insertOn( $fooDbw );

		$fooItemPage->updateRevisionOn( $fooDbw, $fooItemRevision );

		$fooLb->reuseConnection( $fooDbw );

		$client = $this->getWikibaseClient();

		$lookup = $client->getRestrictedEntityLookup();
		$this->assertFalse( $lookup->hasEntity( new ItemId( 'Q123' ) ) );
		$this->assertTrue( $lookup->hasEntity( new ItemId( 'foo:Q123' ) ) );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		return new WikibaseClient(
			new SettingsArray( WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy() ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			[
				'foo' => new SettingsArray( [ 'repoDatabase' => 'foowiki' ] ),
			],
			new HashSiteStore()
		);
	}

}