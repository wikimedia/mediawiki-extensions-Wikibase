<?php

namespace Wikibase\DataAccess;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Services\ServiceContainer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Store\EntityStoreWatcher;

/**
 * A service locator for services configured for a particular repository.
 * Services are defined by loading a wiring array(s), or by using defineService method.
 *
 * @license GPL-2.0-or-later
 */
class PerRepositoryServiceContainer extends ServiceContainer implements DataAccessServices, EntityStoreWatcher {

	/**
	 * @var string|false
	 */
	private $databaseName;

	/**
	 * @var string
	 */
	private $repositoryName;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityIdComposer
	 */
	private $entityIdComposer;

	/**
	 * @var DataValueDeserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var callable[]
	 */
	private $deserializerFactoryCallbacks;

	/**
	 * @param string|false $databaseName
	 * @param string $repositoryName
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param DataValueDeserializer $dataValueDeserializer
	 * @param GenericServices $genericServices
	 * @param DataAccessSettings $settings
	 * @param callable[] $deserializerFactoryCallbacks
	 */
	public function __construct(
		$databaseName,
		$repositoryName,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		DataValueDeserializer $dataValueDeserializer,
		GenericServices $genericServices,
		DataAccessSettings $settings,
		array $deserializerFactoryCallbacks
	) {
		parent::__construct( [ $genericServices, $settings ] );

		$this->databaseName = $databaseName;
		$this->repositoryName = $repositoryName;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->deserializerFactoryCallbacks = $deserializerFactoryCallbacks;
	}

	/**
	 * @return string
	 */
	public function getRepositoryName() {
		return $this->repositoryName;
	}

	/**
	 * @return string|false
	 */
	public function getDatabaseName() {
		return $this->databaseName;
	}

	/**
	 * @return EntityIdParser
	 */
	public function getEntityIdParser() {
		return $this->entityIdParser;
	}

	/**
	 * @return EntityIdComposer
	 */
	public function getEntityIdComposer() {
		return $this->entityIdComposer;
	}

	/**
	 * @return DataValueDeserializer
	 */
	public function getDataValueDeserializer() {
		return $this->dataValueDeserializer;
	}

	/**
	 * Returns a deserializer to deserialize entities. Returned deserializer is configured
	 * to add relevant repository prefixes.
	 *
	 * @return Deserializer
	 */
	public function getEntityDeserializer() {
		$deserializerFactory = new DeserializerFactory(
			$this->dataValueDeserializer,
			$this->entityIdParser
		);

		$deserializers = [];
		foreach ( $this->deserializerFactoryCallbacks as $callback ) {
			$deserializers[] = call_user_func( $callback, $deserializerFactory );
		}

		$internalDeserializerFactory = new InternalDeserializerFactory(
			$this->dataValueDeserializer,
			$this->entityIdParser,
			new DispatchingDeserializer( $deserializers )
		);

		return $internalDeserializerFactory->newEntityDeserializer();
	}

	/**
	 * @see EntityStoreWatcher::entityUpdated
	 *
	 * @param EntityRevision $entityRevision
	 */
	public function entityUpdated( EntityRevision $entityRevision ) {
		foreach ( $this->getServiceNames() as $serviceName ) {
			$service = $this->peekService( $serviceName );
			if ( $service instanceof EntityStoreWatcher ) {
				$service->entityUpdated( $entityRevision );
			}
		}
	}

	/**
	 * @see EntityStoreWatcher::entityDeleted
	 *
	 * @param EntityId $entityId
	 */
	public function entityDeleted( EntityId $entityId ) {
		foreach ( $this->getServiceNames() as $serviceName ) {
			$service = $this->peekService( $serviceName );
			if ( $service instanceof EntityStoreWatcher ) {
				$service->entityDeleted( $entityId );
			}
		}
	}

	/**
	 * @see EntityStoreWatcher::redirectUpdated
	 *
	 * @param EntityRedirect $entityRedirect
	 * @param int $revisionId
	 */
	public function redirectUpdated( EntityRedirect $entityRedirect, $revisionId ) {
		foreach ( $this->getServiceNames() as $serviceName ) {
			$service = $this->peekService( $serviceName );
			if ( $service instanceof EntityStoreWatcher ) {
				$service->redirectUpdated( $entityRedirect, $revisionId );
			}
		}
	}

	public function getEntityInfoBuilder() {
		return $this->getService( 'EntityInfoBuilder' );
	}

	public function getEntityInfoBuilderFactory() {
		return $this->getService( 'EntityInfoBuilderFactory' );
	}

	public function getEntityPrefetcher() {
		return $this->getService( 'EntityPrefetcher' );
	}

	public function getEntityRevisionLookup() {
		return $this->getService( 'EntityRevisionLookup' );
	}

	public function getPropertyInfoLookup() {
		return $this->getService( 'PropertyInfoLookup' );
	}

	public function getTermBuffer() {
		return $this->getService( 'TermBuffer' );
	}

	public function getTermSearchInteractorFactory() {
		return $this->getService( 'TermSearchInteractorFactory' );
	}

}
