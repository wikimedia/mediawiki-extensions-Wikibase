<?php

declare( strict_types=1 );

namespace Wikibase\DataAccess\Tests;

use Deserializers\Deserializer;
use MediaWiki\Storage\NameTableStoreFactory;
use PHPUnit\Framework\TestCase;
use Serializers\Serializer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;

/**
 * @license GPL-2.0-or-later
 * @group Wikibase
 * @covers \Wikibase\DataAccess\SingleEntitySourceServicesFactory
 */
class SingleEntitySourceServicesFactoryTest extends TestCase {
	public function testGetServicesForSource(): void {
		$entitySource = new DatabaseEntitySource(
			'test',
			false,
			[],
			'',
			'',
			'',
			''
		);

		$dependencies = array_map( [ $this, 'createMock' ],
			[
				EntityIdParser::class,
				EntityIdComposer::class,
				Deserializer::class,
				NameTableStoreFactory::class,
				DataAccessSettings::class,
				LanguageFallbackChainFactory::class,
				Serializer::class,
				EntityTypeDefinitions::class,
				RepoDomainDbFactory::class,
			]
		);

		$servicesFactory = new SingleEntitySourceServicesFactory( ...$dependencies );
		$singleSourceServices = $servicesFactory->getServicesForSource( $entitySource );

		$this->assertSame(
			$entitySource,
			$singleSourceServices->getEntitySource(),
			'Returns services for source'
		);

		$this->assertSame(
			$singleSourceServices,
			$servicesFactory->getServicesForSource( $entitySource ),
			'Caches services by source'
		);
	}
}
