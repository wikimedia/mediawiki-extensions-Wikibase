<?php

namespace Wikibase\DataAccess;

use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Serializers\DataValueSerializer;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Services\ServiceContainer;
use Serializers\DispatchingSerializer;
use Wikibase\Client\Serializer\ForbiddenSerializer;
use Wikibase\Client\WikibaseClient;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\WikibaseSettings;

/**
 * A service locator for services configured for a particular repository.
 * Services are defined by loading a wiring array(s), or by using defineService method.
 *
 * @license GPL-2.0+
 */
class RepositoryServiceContainer extends ServiceContainer implements DataAccessServices, EntityStoreWatcher {

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
	private $serializerFactoryCallbacks;

	/**
	 * @var callable[]
	 */
	private $deserializerFactoryCallbacks;

	private $maxSerializedEntitySize;

	/**
	 * @param string|false $databaseName
	 * @param string $repositoryName
	 * @param EntityIdParser $entityIdParser
	 * @param EntityIdComposer $entityIdComposer
	 * @param DataValueDeserializer $dataValueDeserializer
	 * @param WikibaseClient $client Top-level factory passed to service instantiators // TODO: fix this!
	 */
	public function __construct(
		$databaseName,
		$repositoryName,
		EntityIdParser $entityIdParser,
		EntityIdComposer $entityIdComposer,
		DataValueDeserializer $dataValueDeserializer,
		WikibaseClient $client
	) {
		parent::__construct( [ $client ] );

		$this->databaseName = $databaseName;
		$this->repositoryName = $repositoryName;
		$this->entityIdParser = $entityIdParser;
		$this->entityIdComposer = $entityIdComposer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		// TODO: pass EntityTypeDefinitions to get those callbacks? or two arrays at least?
		$this->serializerFactoryCallbacks = $client->getEntitySerializerFactoryCallbacks();
		$this->deserializerFactoryCallbacks = $client->getEntityDeserializerFactoryCallbacks();
		$this->maxSerializedEntitySize = $client->getSettings()->getSetting( 'maxSerializedEntitySize' );
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

	public function getEntitySerializer() {
		if ( !WikibaseSettings::isRepoEnabled() ) {
			return new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
		}

		$baseSerializerFactory = new SerializerFactory( new DataValueSerializer() );
		$serializers = [];

		foreach ( $this->serializerFactoryCallbacks as $callback ) {
			$serializers[] = call_user_func( $callback, $baseSerializerFactory );
		}

		return new DispatchingSerializer( $serializers );
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

	/**
	 * @return EntityContentDataCodec
	 */
	public function getEntityContentDataCodec() {
		return new EntityContentDataCodec(
			$this->getEntityIdParser(),
			$this->getEntitySerializer(),
			$this->getEntityDeserializer(),
			$this->maxSerializedEntitySize * 1024
		);
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
