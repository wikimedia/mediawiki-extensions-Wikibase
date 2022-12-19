<?php

namespace Wikibase\Repo\Tests\Unit\ServiceWiring;

use Wikibase\DataAccess\ApiEntitySource;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FederatedProperties\FederatedPropertyId;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\SubEntityTypesMapper;
use Wikibase\Repo\FederatedProperties\FederatedPropertiesAwareDispatchingEntityIdParser;
use Wikibase\Repo\Tests\Unit\ServiceWiringTestCase;

/**
 * @coversNothing
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityIdParserTest extends ServiceWiringTestCase {

	public function testConstruction() {
		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions',
			new EntityTypeDefinitions( [
				'test' => [
					EntityTypeDefinitions::ENTITY_ID_PATTERN => '/^T[1-9][0-9]*$/',
					EntityTypeDefinitions::ENTITY_ID_BUILDER => function ( $serialization ) {
						return new ItemId( 'Q1000' . substr( $serialization, 1 ) );
					},
				],
			] ) );

		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => false,
			] ) );

		/** @var EntityIdParser $entityIdParser */
		$entityIdParser = $this->getService( 'WikibaseRepo.EntityIdParser' );
		$this->assertInstanceOf( EntityIdParser::class, $entityIdParser );

		$entityId = $entityIdParser->parse( 'T123' );
		$this->assertInstanceOf( ItemId::class, $entityId );
		$this->assertSame( 'Q1000123', $entityId->getSerialization() );
	}

	public function testConstructionWhenFederatedPropertiesIsEnabled() {
		$entityTypeDefinitions = new EntityTypeDefinitions( [
			'test' => [
				EntityTypeDefinitions::ENTITY_ID_PATTERN => '/^T[1-9][0-9]*$/',
				EntityTypeDefinitions::ENTITY_ID_BUILDER => function ( $serialization ) {
					return new ItemId( 'Q1000' . substr( $serialization, 1 ) );
				},
			],
		] );

		$this->mockService( 'WikibaseRepo.EntityTypeDefinitions', $entityTypeDefinitions );

		$this->mockService( 'WikibaseRepo.Settings',
			new SettingsArray( [
				'federatedPropertiesEnabled' => true,
			] ) );

		$this->mockService( 'WikibaseRepo.EntitySourceDefinitions',
			new EntitySourceDefinitions( [
				new DatabaseEntitySource(
					'local',
					false,
					[],
					'http://www.localhost.org/entity/',
					'',
					'',
					''
				),
				new ApiEntitySource(
					'wikidorta',
					[ 'property' ],
					'http://www.wikidorta.org/shmentity/',
					'',
					'',
					''
				),
			], new SubEntityTypesMapper( [] ) ) );

		/** @var EntityIdParser $entityIdParser */
		$entityIdParser = $this->getService( 'WikibaseRepo.EntityIdParser' );
		$this->assertInstanceOf( FederatedPropertiesAwareDispatchingEntityIdParser::class, $entityIdParser );

		$entityId = $entityIdParser->parse( 'http://www.wikidorta.org/shmentity/P123' );
		$this->assertInstanceOf( FederatedPropertyId::class, $entityId );
		$this->assertSame( 'http://www.wikidorta.org/shmentity/P123', $entityId->getSerialization() );
	}

}
