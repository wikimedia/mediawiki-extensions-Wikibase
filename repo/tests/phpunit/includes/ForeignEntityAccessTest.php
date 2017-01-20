<?php

namespace Wikibase\Repo\Tests;

use Database;
use Exception;
use FakeResultWrapper;
use HashSiteStore;
use LBFactory;
use LoadBalancer;
use MediaWikiTestCase;
use stdClass;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookupException;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;

/**
 * Tests for services configured to access entity data from foreign repositories.
 * Currently those cover optional use of services configured in the Client component in Repo.
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class ForeignEntitiyAccessTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		if ( !defined( 'WBC_VERSION' ) ) {
			$this->markTestSkipped( 'WikibaseClient must be enabled to run this test' );
		}
	}

	private function getRowObject( array $fields ) {
		$row = new stdClass();
		foreach ( $fields as $field => $value ) {
			$row->$field = $value;
		}
		return $row;
	}

	/**
	 * @param array $returnValues Associative array mapping table names used in DB queries to values that query should return
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|Database
	 */
	private function getMockDb( $returnValues ) {
		$db = $this->getMockBuilder( Database::class )
			->disableOriginalConstructor()
			->getMock();
		$db->expects( $this->any() )
			->method( 'select' )
			->willReturnCallback( function( $table ) use ( $returnValues ) {
				if ( is_array( $table ) ) {
					$table = $table[0];
				}
				if ( isset( $returnValues[$table] ) ) {
					return $returnValues[$table];
				}
				return null;
			} );
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

	public function testEntityRevisionLookupReadsDataFromDatabaseOfRepositoryEntityBelongsTo() {
		$localDbName = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'changesDatabase' );

		$defaultLb = $this->getMockLoadBalancer( $this->getMockDb( [ 'page' => new FakeResultWrapper( [] ) ] ) );
		$lbFactory = $this->getLoadBalancerFactory( [
			'foowiki' => $this->getMockLoadBalancer(
				$this->getMockDb(
					[ 'page' => new FakeResultWrapper( [ $this->getRowObject( [
						'rev_id' => 1,
						'rev_content_format' => null,
						'rev_timestamp' => '20160101010101',
						'page_title' => 'Q123',
						'page_latest' => 1,
						'page_is_redirect' => 0,
						'old_text' => '{"type":"item","id":"Q123","labels":{"en":{"language":"en","value":"Foo Item"}}}',
					] ) ] )
				] )
			),
			$localDbName => $defaultLb,
		] );
		$this->overrideMwServices( null, [
			'DBLoadBalancerFactory' => function () use ( $lbFactory ) {
				return $lbFactory;
			},
			'DBLoadBalancer' => function () use ( $defaultLb ) {
				return $defaultLb;
			},
		] );

		$repo = $this->getWikibaseRepo();
		$lookup = $repo->getEntityLookup();

		$this->assertTrue( $lookup->hasEntity( new ItemId( 'foo:Q123' ) ) );
		$this->assertFalse( $lookup->hasEntity( new ItemId( 'Q123' ) ) );

		$entity = $lookup->getEntity( new ItemId( 'foo:Q123' ) );
		$this->assertEquals( 'foo:Q123', $entity->getId()->getSerialization() );

		$this->assertNull( $lookup->getEntity( new ItemId( 'Q123' ) ) );
	}

	public function testTermLookupReadsDataFromDatabaseOfRepositoryEntityBelongsTo() {
		$localDbName = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'changesDatabase' );

		$defaultLb = $this->getMockLoadBalancer( $this->getMockDb( [ 'wb_terms' => new FakeResultWrapper( [] ) ] ) );
		$lbFactory = $this->getLoadBalancerFactory( [
			'foowiki' => $this->getMockLoadBalancer(
				$this->getMockDb( [
					'wb_terms' => new FakeResultWrapper( [ $this->getRowObject( [
						'term_entity_type' => 'item',
						'term_type' => 'label',
						'term_language' => 'en',
						'term_text' => 'Foo Item',
						'term_entity_id' => 123,
					] ) ] )
				] )
			),
			$localDbName => $defaultLb,
		] );
		$this->overrideMwServices( null, [
			'DBLoadBalancerFactory' => function () use ( $lbFactory ) {
				return $lbFactory;
			},
			'DBLoadBalancer' => function () use ( $defaultLb ) {
				return $defaultLb;
			},
		] );

		$repo = $this->getWikibaseRepo();
		$lookup = $repo->getTermLookup();

		$this->assertSame( 'Foo Item', $lookup->getLabel( new ItemId( 'foo:Q123' ), 'en' ) );
		$this->assertNull( $lookup->getLabel( new ItemId( 'foo:Q123' ), 'de' ) );
		$this->assertNull( $lookup->getLabel( new ItemId( 'Q123' ), 'en' ) );
	}

	public function testPropertyInfoLookupReadsDataFromDatabaseOfRepositoryEntityBelongsTo() {
		$localDbName = WikibaseRepo::getDefaultInstance()->getSettings()->getSetting( 'changesDatabase' );

		$defaultLb = $this->getMockLoadBalancer( $this->getMockDb( [
			'page' => new FakeResultWrapper( [] ),
			'wb_property_info' => new FakeResultWrapper( [] ),
		] ) );
		$lbFactory = $this->getLoadBalancerFactory( [
			'foowiki' => $this->getMockLoadBalancer(
				$this->getMockDb( [
					'page' => new FakeResultWrapper( [ $this->getRowObject( [
						'page_title' => 'P321',
					] ) ] ),
					'wb_property_info' => new FakeResultWrapper( [ $this->getRowObject( [
						'pi_property_id' => 321,
						'pi_info' => '{"type":"string"}',
					] ) ] ),
				] )
			),
			$localDbName => $defaultLb,
		] );
		$this->overrideMwServices( null, [
			'DBLoadBalancerFactory' => function () use ( $lbFactory ) {
				return $lbFactory;
			},
			'DBLoadBalancer' => function () use ( $defaultLb ) {
				return $defaultLb;
			},
		] );

		$repo = $this->getWikibaseRepo();
		$lookup = $repo->getPropertyDataTypeLookup();

		$this->assertSame( 'string', $lookup->getDataTypeIdForProperty( new PropertyId( 'foo:P321' ) ) );

		try {
			$lookup->getDataTypeIdForProperty( new PropertyId( 'foo:P123' ) );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( PropertyDataTypeLookupException::class, $e );
		}

		try {
			$lookup->getDataTypeIdForProperty( new PropertyId( 'P321' ) );
		} catch ( Exception $e ) {
			$this->assertInstanceOf( PropertyDataTypeLookupException::class, $e );
		}
	}

	private function getWikibaseRepo() {
		$client = $this->getWikibaseClient();
		return new WikibaseRepo(
			new SettingsArray( WikibaseRepo::getDefaultInstance()->getSettings()->getArrayCopy() ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [] ),
			$client->getEntityDataRetrievalServiceFactory(),
			$client->getSettings()
		);
	}

	/**
	 * @return WikibaseClient
	 */
	private function getWikibaseClient() {
		$settings = WikibaseClient::getDefaultInstance()->getSettings()->getArrayCopy();
		$settings['foreignRepositories'] = $this->getForeignRepositorySettings();
		return new WikibaseClient(
			new SettingsArray( $settings ),
			new DataTypeDefinitions( [] ),
			new EntityTypeDefinitions( [
				'item' => [
					'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
						return $deserializerFactory->newItemDeserializer();
					},
					'entity-id-pattern' => ItemId::PATTERN,
					'entity-id-builder' => function( $serialization ) {
						return new ItemId( $serialization );
					},
					'entity-id-composer-callback' => function( $repositoryName, $uniquePart ) {
						return new ItemId( EntityId::joinSerialization( [
							$repositoryName,
							'',
							'Q' . $uniquePart
						] ) );
					},
				],
				'property' => [
					'deserializer-factory-callback' => function( DeserializerFactory $deserializerFactory ) {
						return $deserializerFactory->newPropertyDeserializer();
					},
					'entity-id-pattern' => PropertyId::PATTERN,
					'entity-id-builder' => function( $serialization ) {
						return new PropertyId( $serialization );
					},
					'entity-id-composer-callback' => function( $repositoryName, $uniquePart ) {
						return new PropertyId( EntityId::joinSerialization( [
							$repositoryName,
							'',
							'P' . $uniquePart
						] ) );
					},
				],
			] ),
			new HashSiteStore()
		);
	}

	private function getForeignRepositorySettings() {
		return [
			'foo' => [ 'repoDatabase' => 'foowiki', 'prefixMapping' => [] ],
		];
	}

}
