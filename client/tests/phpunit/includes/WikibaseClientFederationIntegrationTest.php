<?php

namespace Wikibase\Client\Tests;

use Database;
use FakeResultWrapper;
use HashSiteStore;
use Language;
use LBFactory;
use LoadBalancer;
use MediaWikiTestCase;
use ResultWrapper;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\SettingsArray;

/**
 * @group Wikibase
 * @group WikibaseClient
 *
 * @license GPL-2.0+
 */
class WikibaseClientFederationIntegrationTest extends MediaWikiTestCase {

	private function getRowObject( array $fields ) {
		$row = new \stdClass();
		foreach ( $fields as $field => $value ) {
			$row->$field = $value;
		}
		return $row;
	}

	/**
	 * @param ResultWrapper|false $returnValue
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|Database
	 */
	private function getMockDb( $returnValue ) {
		$db = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
		$db->expects( $this->any() )
			->method( 'select' )
			->willReturn( $returnValue );
		return $db;
	}

	private function getMockLoadBalancer( Database $db ) {
		$loadBalancer = $this->getMockBuilder( LoadBalancer::class )
			->disableOriginalConstructor()
			->getMock();
		$loadBalancer->expects( $this->any() )
			->method( 'getConnection' )
			->willReturn( $db );
		return $loadBalancer;
	}

	private function getLoadBalancerFactory( array $loadBalancers ) {
		$factory = $this->getMockBuilder( LBFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$factory->expects( $this->any() )
			->method( 'getMainLB' )
			->willReturnCallback( function( $wiki ) use ( $loadBalancers ) {
				return $loadBalancers[$wiki];
			} );
		return $factory;
	}

	public function testFoo() {
		$defaultLb = $this->getMockLoadBalancer( $this->getMockDb( new FakeResultWrapper( [] ) ) );
		$lbFactory = $this->getLoadBalancerFactory( [
			'foowiki' => $this->getMockLoadBalancer(
				$this->getMockDb( new FakeResultWrapper( [ $this->getRowObject( [
					'rev_id' => 1,
					'rev_content_format' => null,
					'rev_timestamp' => '20160101010101',
					'page_title' => 'Q123',
					'page_latest' => 1,
					'page_is_redirect' => 0,
					'old_id' => 1,
					'old_text' => '{"type":"item","id":"Q123","labels":{"en":{"language":"en","value":"Foo Item"}}' .
						',"descriptions":[],"aliases":[],"claims":[],"sitelinks":[]}',
					'old_flags' => 'utf-8',
				] ) ] ) )
			),
			false => $defaultLb,
		] );
		$this->overrideMwServices( null, [
			'DBLoadBalancerFactory' => function () use ( $lbFactory ) {
				return $lbFactory;
			},
			'DBLoadBalancer' => function () use ( $defaultLb ) {
				return $defaultLb;
			},
		] );

		$client = $this->getWikibaseClient();
		$lookup = $client->getRestrictedEntityLookup();

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'foo:Q123' ) ) );
		$this->assertFalse( $lookup->hasEntity( new ItemId( 'Q123' ) ) );

		$entity = $lookup->getEntity( new ItemId( 'foo:Q123' ) );
		$this->assertEquals( 'foo:Q123', $entity->getId()->getSerialization() );
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy();
		$settings['foreignRepositories'] = $this->getForeignRepositorySettings();
		return new WikibaseClient(
			new SettingsArray( $settings ),
			Language::factory( 'en' ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [
				'item' => [
					'serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
						return $serializerFactory->newItemSerializer();
					},
					'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
						return $deserializerFactory->newItemDeserializer();
					},
					'entity-id-pattern' => ItemId::PATTERN,
					'entity-id-builder' => function( $serialization ) {
						return new ItemId( $serialization );
					},
				],
			] ),
			new HashSiteStore()
		);
	}

	private function getForeignRepositorySettings() {
		return [
			'foo' => [ 'repoDatabase' => 'foowiki', 'prefixMapping' => [ 'bar' => 'xyz' ] ],
		];
	}

}
