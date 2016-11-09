<?php

namespace Wikibase\Lib\Store;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;

/**
 * A factory providing the WikiPageEntityMetaDataLookup instance configured for the given foreign repository.
 *
 * @license GPL-2.0+
 */
class ForeignEntityRevisionLookupFactory {

	/**
	 * @var PrefixMappingEntityIdParserFactory
	 */
	private $parserFactory;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var DataValueDeserializer
	 */
	private $dataValueDeserializer;

	/**
	 * @var EntityNamespaceLookup
	 */
	private $entityNamespaceLookup;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @var string
	 */
	private $maxBlobSize;

	/**
	 * @var string[]
	 */
	private $databaseNames;

	/**
	 * @param PrefixMappingEntityIdParserFactory $parserFactory
	 * @param DataValueDeserializer $dataValueDeserializer
	 * @param EntityIdParser $idParser Parser used to create an EntityId with a foreign repository prefix stripped
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param string $maxBlobSize The maximum size of a blob allowed in serialization/deserialization,
	 *               @see EntityContentDataCodec
	 * @param string[] $databaseNames Associative array mapping repository names (prefixes) to database names
	 */
	public function __construct(
		PrefixMappingEntityIdParserFactory $parserFactory,
		Serializer $entitySerializer,
		DataValueDeserializer $dataValueDeserializer,
		EntityNamespaceLookup $entityNamespaceLookup,
		EntityIdParser $idParser,
		$maxBlobSize,
		array $databaseNames
	) {
		$this->parserFactory = $parserFactory;
		$this->entitySerializer = $entitySerializer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
		$this->idParser = $idParser;
		$this->maxBlobSize = $maxBlobSize;
		$this->databaseNames = $databaseNames;
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return WikiPageEntityRevisionLookup
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	public function getLookup( $repositoryName ) {
		if ( !array_key_exists( $repositoryName, $this->databaseNames ) ) {
			throw new UnknownForeignRepositoryException( 'No database configured for repository: ' . $repositoryName );
		}

		$prefixMappingIdParser = $this->parserFactory->getIdParser( $repositoryName );
		$entityDeserializerFactory = new DeserializerFactory(
			$this->dataValueDeserializer,
			$prefixMappingIdParser,
			null
		);
		$codec = new EntityContentDataCodec(
			$prefixMappingIdParser,
			$this->entitySerializer,
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
