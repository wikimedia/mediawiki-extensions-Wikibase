<?php

namespace Wikibase\DataAccess;

use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;

/**
 * A factory providing PerRepositoryServiceContainer objects configured for given repository.
 * PerRepositoryServiceContainers are initialized using wiring files provided in the constructor.
 *
 * @license GPL-2.0+
 */
class PerRepositoryServiceContainerFactory {

	/**
	 * @var PrefixMappingEntityIdParserFactory
	 */
	private $idParserFactory;

	/**
	 * @var EntityIdComposer
	 */
	private $idComposer;

	/**
	 * @var RepositorySpecificDataValueDeserializerFactory
	 */
	private $dataValueDeserializerFactory;

	/**
	 * Associative array mapping repository names to database names (string or false)
	 *
	 * @var array
	 */
	private $databaseNames;

	/**
	 * @var callable[]
	 */
	private $serviceWiring;

	/**
	 * @var GenericServices
	 */
	private $genericServices;

	/**
	 * @var DataAccessSettings
	 */
	private $settings;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @param PrefixMappingEntityIdParserFactory $idParserFactory
	 * @param EntityIdComposer $idComposer
	 * @param RepositorySpecificDataValueDeserializerFactory $dataValueDeserializerFactory
	 * @param array $repositoryDatabaseNames
	 * @param callable[] $serviceWiring
	 * @param GenericServices $genericServices
	 * @param DataAccessSettings $settings
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct(
		PrefixMappingEntityIdParserFactory $idParserFactory,
		EntityIdComposer $idComposer, //  TODO: change ID Composer and pass a factory of prefixing composer (T165589)
		RepositorySpecificDataValueDeserializerFactory $dataValueDeserializerFactory,
		array $repositoryDatabaseNames,
		array $serviceWiring,
		GenericServices $genericServices,
		DataAccessSettings $settings,
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->idParserFactory = $idParserFactory;
		$this->idComposer = $idComposer;
		$this->dataValueDeserializerFactory = $dataValueDeserializerFactory;
		$this->databaseNames = $repositoryDatabaseNames;
		$this->serviceWiring = $serviceWiring;
		$this->genericServices = $genericServices;
		$this->settings = $settings;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return PerRepositoryServiceContainer
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	public function newContainer( $repositoryName ) {
		if ( !array_key_exists( $repositoryName, $this->databaseNames ) ) {
			throw new UnknownForeignRepositoryException( $repositoryName );
		}

		$container = new PerRepositoryServiceContainer(
			$this->databaseNames[$repositoryName],
			$repositoryName,
			$this->idParserFactory->getIdParser( $repositoryName ),
			$this->idComposer,
			$this->dataValueDeserializerFactory->getDeserializer( $repositoryName ),
			$this->genericServices,
			$this->settings,
			$this->entityTypeDefinitions->getDeserializerFactoryCallbacks()
		);
		$container->applyWiring( $this->serviceWiring );

		return $container;
	}

}
