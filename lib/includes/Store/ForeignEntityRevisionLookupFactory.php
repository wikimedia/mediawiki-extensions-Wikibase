<?php

namespace Wikibase\Lib\Store;

use DataValues\Deserializers\DataValueDeserializer;
use Serializers\Serializer;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

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
	 * @var int
	 */
	private $maxBlobSize;

	/**
	 * @var string[]
	 */
	private $databaseNames;

	/**
	 * @var WikiPageEntityRevisionLookup[]
	 */
	private $lookups = [];

	/**
	 * @param PrefixMappingEntityIdParserFactory $parserFactory
	 * @param DataValueDeserializer $dataValueDeserializer
	 * @param EntityIdParser $idParser Parser used to create an EntityId with a foreign repository prefix stripped
	 * @param EntityNamespaceLookup $entityNamespaceLookup
	 * @param int $maxBlobSize The maximum size of a blob allowed in serialization/deserialization,
	 *            @see EntityContentDataCodec
	 * @param string[] $databaseNames Associative array mapping repository names (prefixes) to database names
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct(
		PrefixMappingEntityIdParserFactory $parserFactory,
		Serializer $entitySerializer,
		DataValueDeserializer $dataValueDeserializer,
		EntityNamespaceLookup $entityNamespaceLookup,
		$maxBlobSize,
		array $databaseNames
	) {
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $databaseNames, '$databaseNames' );
		Assert::parameterElementType( 'string', $databaseNames, '$databaseNames' );
		Assert::parameter( !array_key_exists( '', $databaseNames ), '$databaseNames', 'must not contain an empty string key' );

		$this->parserFactory = $parserFactory;
		$this->entitySerializer = $entitySerializer;
		$this->dataValueDeserializer = $dataValueDeserializer;
		$this->entityNamespaceLookup = $entityNamespaceLookup;
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
		if ( !isset( $this->lookups[$repositoryName] ) ) {
			$this->lookups[$repositoryName] = $this->newLookupForRepository( $repositoryName );

		}
		return $this->lookups[$repositoryName];
	}

	/**
	 * @param string $repositoryName
	 *
	 * @return WikiPageEntityRevisionLookup
	 *
	 * @throws UnknownForeignRepositoryException
	 */
	private function newLookupForRepository( $repositoryName ) {
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
				$this->databaseNames[$repositoryName],
				$repositoryName
			)
		);

		return new WikiPageEntityRevisionLookup(
			$codec,
			$metaDataLookup,
			$this->databaseNames[$repositoryName]
		);
	}

}
