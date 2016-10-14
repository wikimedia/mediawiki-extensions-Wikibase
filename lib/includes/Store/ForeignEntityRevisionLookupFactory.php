<?php

namespace Wikibase\Lib\Store;

use DataValues\Deserializers\DataValueDeserializer;
use Wikibase\Client\ForbiddenSerializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;

/**
 * @license GPL-2.0+
 */
class ForeignEntityRevisionLookupFactory {

	private $idParser;

	private $parserFactory;

	private $dataValueDeserializer;

	private $entityNamespaceLookup;

	private $databaseNames;

	private $maxBlobSize;

	public function __construct(
		PrefixMappingEntityIdParserFactory $parserFactory,
		DataValueDeserializer $dataValueDeserializer,
		EntityIdParser $idParser,
		EntityNamespaceLookup $entityNamespaceLookup,
		$maxBlobSize,
		array $databaseNames
	) {
		$this->idParser = $idParser;
		$this->parserFactory = $parserFactory;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->maxBlobSize = $maxBlobSize;
		$this->databaseNames = $databaseNames;
	}

	public function getLookup( $repositoryName ) {
		$prefixMappingIdParser = $this->parserFactory->getIdParser( $repositoryName );
		$entityDeserializerFactory = new DeserializerFactory(
			$this->dataValueDeserializer,
			$prefixMappingIdParser,
			null
		);
		// TODO: inject serializer
		$forbiddenSerializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );
		$codec = new EntityContentDataCodec(
			$prefixMappingIdParser,
			$forbiddenSerializer,
			$entityDeserializerFactory->newEntityDeserializer(),
			$this->maxBlobSize
		);
		$metaDataLookup = new PrefetchingWikiPageEntityMetaDataAccessor(
			new WikiPageEntityMetaDataLookup(
				$this->entityNamespaceLookup,
				$this->databaseNames[$repositoryName]
			)
		);
		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataLookup,
			$this->idParser,
			$this->databaseNames[$repositoryName],
			$repositoryName
		);
	}

}
