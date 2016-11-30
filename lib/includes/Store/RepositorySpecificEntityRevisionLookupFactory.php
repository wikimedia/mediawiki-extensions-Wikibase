<?php

namespace Wikibase\Lib\Store;

use Serializers\Serializer;
use Wikibase\DataModel\Assert\RepositoryNameAssert;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\UnknownForeignRepositoryException;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\Sql\PrefetchingWikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataLookup;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * A factory providing the WikiPageEntityRevisionLookup instance configured for the given repository.
 *
 * @license GPL-2.0+
 */
class RepositorySpecificEntityRevisionLookupFactory {

	/**
	 * @var PrefixMappingEntityIdParserFactory
	 */
	private $parserFactory;

	/**
	 * @var Serializer
	 */
	private $entitySerializer;

	/**
	 * @var RepositorySpecificDataValueDeserializerFactory
	 */
	private $dataValueDeserializerFactory;

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
	 * @param Serializer $entitySerializer
	 * @param RepositorySpecificDataValueDeserializerFactory $dataValueDeserializerFactory
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
		RepositorySpecificDataValueDeserializerFactory $dataValueDeserializerFactory,
		EntityNamespaceLookup $entityNamespaceLookup,
		$maxBlobSize,
		array $databaseNames
	) {
		Assert::parameter( !empty( $databaseNames ), '$databaseNames', 'must not be empty' );
		foreach ( $databaseNames as $name ) {
			Assert::parameter( is_string( $name ) || $name === false, 'values of $databaseNames', 'must be either string or false' );
		}
		RepositoryNameAssert::assertParameterKeysAreValidRepositoryNames( $databaseNames, '$databaseNames' );

		$this->parserFactory = $parserFactory;
		$this->entitySerializer = $entitySerializer;
		$this->dataValueDeserializerFactory = $dataValueDeserializerFactory;
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
			$this->dataValueDeserializerFactory->getDeserializer( $repositoryName ),
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
