<?php

namespace Wikibase\DataAccess\Tests;

use MediaWiki\Storage\NameTableStore;
use MediaWiki\Storage\NameTableStoreFactory;
use Wikibase\DataAccess\GenericServices;
use Wikibase\DataAccess\MultiRepositoryServices;
use Wikibase\DataAccess\PerRepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class MultiRepositoryServiceWiringTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @return NameTableStoreFactory|object
	 */
	private function getNameTableStoreFactoryProphecy() {
		$prophecy = $this->prophesize( NameTableStoreFactory::class );
		$prophecy->getSlotRoles( false )
			->willReturn( $this->prophesize( NameTableStore::class )
			->reveal() );
		return $prophecy->reveal();
	}

	/**
	 * @return PerRepositoryServiceContainerFactory
	 */
	private function getPerRepositoryServiceContainerFactory() {
		$idParser = new PrefixMappingEntityIdParserFactory(
			new BasicEntityIdParser(), []
		);

		$entityTypeDefinitions = new EntityTypeDefinitions( [] );

		return new PerRepositoryServiceContainerFactory(
			$idParser,
			new EntityIdComposer( [] ),
			new RepositorySpecificDataValueDeserializerFactory( $idParser ),
			[ '' => false ],
			require __DIR__ . '/../../src/PerRepositoryServiceWiring.php',
			new GenericServices( $entityTypeDefinitions, [] ),
			DataAccessSettingsFactory::repositoryPrefixBasedFederation(),
			$entityTypeDefinitions,
			$this->getNameTableStoreFactoryProphecy()
		);
	}

	/**
	 * @return MultiRepositoryServices
	 */
	private function getMultiRepositoryServices() {
		$services = new MultiRepositoryServices(
			$this->getPerRepositoryServiceContainerFactory(),
			new RepositoryDefinitions(
				[ '' => [
					'database' => false,
					'base-uri' => '',
					'entity-namespaces' => [],
					'prefix-mapping' => [],
				] ],
				new EntityTypeDefinitions( [] )
			)
		);

		$services->loadWiringFiles( [ __DIR__ . '/../../src/MultiRepositoryServiceWiring.php' ] );
		return $services;
	}

	public function testGetServiceNames() {
		$services = $this->getMultiRepositoryServices();

		$this->assertEquals(
			[
				'EntityInfoBuilder',
				'EntityPrefetcher',
				'EntityRevisionLookup',
				'PropertyInfoLookup',
				'TermBuffer',
				'TermSearchInteractorFactory',
			],
			$services->getServiceNames()
		);
	}

}
