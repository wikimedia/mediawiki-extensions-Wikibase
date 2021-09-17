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
use Wikibase\Lib\Rdbms\RepoDomainDbFactory;

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
	 * @var RepoDomainDbFactory
	 */
	private $repoDomainDbFactory;

	public function __construct(
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		Deserializer $dataValueDeserializer,
		NameTableStoreFactory $nameTableStoreFactory,
		DataAccessSettings $dataAccessSettings,
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		Serializer $storageEntitySerializer,
		EntityTypeDefinitions $entityTypeDefinitions,
		RepoDomainDbFactory $repoDomainDbFactory
	) {
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->nameTableStoreFactory = $nameTableStoreFactory;
		$this->dataAccessSettings = $dataAccessSettings;
		$this->languageFallbackChainFactory = $languageFallbackChainFactory;
		$this->storageEntitySerializer = $storageEntitySerializer;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->repoDomainDbFactory = $repoDomainDbFactory;

		$this->servicesBySource = [];
	}

	public function getServicesForSource( DatabaseEntitySource $source ): SingleEntitySourceServices {
		$sourceName = $source->getSourceName();

		if ( !array_key_exists( $sourceName, $this->servicesBySource ) ) {
			$this->servicesBySource[ $sourceName ] = $this->newServicesForSource( $source );
		}

		return $this->servicesBySource[ $sourceName ];
	}

	private function newServicesForSource( DatabaseEntitySource $source ): SingleEntitySourceServices {
		return new SingleEntitySourceServices(
			$this->entityIdParser,
			$this->entityIdComposer,
			$this->dataValueDeserializer,
			$this->nameTableStoreFactory->getSlotRoles( $source->getDatabaseName() ),
			$this->dataAccessSettings,
			$source,
			$this->languageFallbackChainFactory,
			$this->storageEntitySerializer,
			$this->repoDomainDbFactory->newForEntitySource( $source ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::DESERIALIZER_FACTORY_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_METADATA_ACCESSOR_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::PREFETCHING_TERM_LOOKUP_CALLBACK ),
			$this->entityTypeDefinitions->get( EntityTypeDefinitions::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK )
		);
	}
}
