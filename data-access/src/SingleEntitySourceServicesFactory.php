<?php
declare( strict_types=1 );

namespace Wikibase\DataAccess;

use Deserializers\Deserializer;
use MediaWiki\Storage\NameTableStoreFactory;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\LanguageFallbackChainFactory;

/**
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServicesFactory {
	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;
	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;
	/**
	 * @var Deserializer
	 */
	private $dataValueDeserializer;
	/**
	 * @var DataAccessSettings
	 */
	private $dataAccessSettings;
	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory;
	/**
	 * @var Serializer
	 */
	private $storageEntitySerializer;
	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;
	/**
	 * @var NameTableStoreFactory
	 */
	private $nameTableStoreFactory;
	/**
	 * @var array
	 */
	private $servicesBySource;

	/**
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param Deserializer $dataValueDeserializer
	 * @param NameTableStoreFactory $nameTableStoreFactory
	 * @param DataAccessSettings $dataAccessSettings
	 * @param LanguageFallbackChainFactory $languageFallbackChainFactory
	 * @param Serializer $storageEntitySerializer
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 */
	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStoreFactory $nameTableStoreFactory,
		DataAccessSettings $dataAccessSettings,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		Serializer $storageEntitySerializer,
		EntityTypeDefinitions $entityTypeDefinitions
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->nameTableStoreFactory = $nameTableStoreFactory;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->storageEntitySerializer = $storageEntitySerializer;
		$this->entityTypeDefinitions = $entityTypeDefinitions;

		$this->servicesBySource = [];
	}

	public function getServicesForSource( EntitySource $source ): SingleEntitySourceServices {
		$sourceName = $source->getSourceName();

		if ( !array_key_exists( $sourceName, $this->servicesBySource ) ) {
			$this->servicesBySource[ $sourceName ] = $this->newServicesForSource( $source );
		}

		return $this->servicesBySource[ $sourceName ];
	}

	private function newServicesForSource( EntitySource $source ): SingleEntitySourceServices {
		return new SingleEntitySourceServices(
			$this->entityIdParser,
			$this->entityIdComposer,
			$this->dataValueDeserializer,
			$this->nameTableStoreFactory->getSlotRoles( $source->getDatabaseName() ),
			$this->dataAccessSettings,
			$source,
			$this->languageFallbackChainFactory,
			$this->storageEntitySerializer,
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_METADATA_ACCESSOR_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK )
		);
	}
}
