<?php

namespace Wikibase\DataAccess;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\DispatchingDeserializer;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\NameTableStore;
use Wikibase\DataAccess\Serializer\ForbiddenSerializer;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\Sql\EntityIdLocalPartPageTableEntityQuery;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\TypeDispatchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikibase\Lib\Store\Sql\WikiPageEntityRevisionLookup;
use Wikibase\WikibaseSettings;

/**
 * @license GPL-2.0-or-later
 */
class SingleEntitySourceServices {

	/**
	 * @var GenericServices
	 */
	private $genericServices;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	private $dataValueDeserializer;

	/**
	 * @var DataAccessSettings
	 */
	private $settings;

	/**
	 * @var EntitySource
	 */
	private $entitySource;

	private $deserializerFactoryCallbacks;
	private $entityMetaDataAccessorCallbacks;
	private $slotRoleStore;

	public function __construct(
		GenericServices $genericServices,
		EntityIdParser $entityIdParser,
		DataValueDeserializer $dataValueDeserializer,
		NameTableStore $slotRoleStore,
		DataAccessSettings $settings,
		EntitySource $entitySource,
		array $deserializerFactoryCallbacks,
		array $entityMetaDataAccessorCallbacks
	) {
		$this->genericServices = $genericServices;
		$this->entityIdParser = $entityIdParser;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->slotRoleStore = $slotRoleStore;
		$this->settings = $settings;
		$this->entitySource = $entitySource;
		$this->deserializerFactoryCallbacks = $deserializerFactoryCallbacks;
		$this->entityMetaDataAccessorCallbacks = $entityMetaDataAccessorCallbacks;
	}

	public function getEntityRevisionLookup() {
		// TODO: only create single instance

		if ( !WikibaseSettings::isRepoEnabled() ) {
			$serializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
		} else {
			$serializer = $this->genericServices->getStorageEntitySerializer();
		}

		$codec = new EntityContentDataCodec(
			$this->entityIdParser,
			$serializer,
			$this->getEntityDeserializer(),
			$this->settings->maxSerializedEntitySizeInBytes()
		);

		/** @var WikiPageEntityMetaDataAccessor $metaDataAccessor */
		$metaDataAccessor = $this->getEntityMetaDataAccessor();

		// TODO: instead calling static getInstance randomly here, inject two db-specific services
		$revisionStoreFactory = MediaWikiServices::getInstance()->getRevisionStoreFactory();
		$blobStoreFactory = MediaWikiServices::getInstance()->getBlobStoreFactory();

		$databaseName = $this->entitySource->getDatabaseName();
		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataAccessor,
			$revisionStoreFactory->getRevisionStore( $databaseName ),
			$blobStoreFactory->newBlobStore( $databaseName ),
			$databaseName
		);
	}

	private function getEntityDeserializer() {
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

	private function getEntityMetaDataAccessor() {
		// TODO: Having this lookup in GenericServices seems shady, this class should
		// probably create/provide one for itself (all data needed in in the entity source)
		$entityNamespaceLookup = $this->genericServices->getEntityNamespaceLookup();
		$repositoryName = '';
		$databaseName = $this->entitySource->getDatabaseName();
		return new PrefetchingWikiPageEntityMetaDataAccessor(
			new TypeDispatchingWikiPageEntityMetaDataAccessor(
				$this->entityMetaDataAccessorCallbacks,
				new WikiPageEntityMetaDataLookup(
					$entityNamespaceLookup,
					new EntityIdLocalPartPageTableEntityQuery(
						$entityNamespaceLookup,
						$this->slotRoleStore
					),
					$databaseName,
					$repositoryName
				),
				$databaseName,
				$repositoryName
			),
			// TODO: inject?
			LoggerFactory::getInstance( 'Wikibase' )
		);
	}

}
