<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;
use Wikimedia\Assert\ParameterTypeException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * EntityIdParser that adds a fixed prefix, maps a prefix of id serialization
 * to a local prefix according to the prefix mapping
 * and parses resulting string as an EntityId.
 * This can be used to prefix IDs of entities coming from a foreign repository
 * with the repository name to avoid clashes with IDs of local entities.
 *
 * @since 3.7
 *
 * @license GPL-2.0+
 */
class PrefixMappingEntityIdParser implements EntityIdParser {

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var string[]
	 */
	private $prefixMapping;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param string $prefix Prefix to be added. It should not contain a colon, in particular at the end of the prefix
	 * @param string[] $prefixMapping
	 * @param EntityIdParser $idParser
	 *
	 * @throws ParameterTypeException
	 * @throws ParameterAssertionException
	 */
	public function __construct( $prefix, array $prefixMapping, EntityIdParser $idParser ) {
		Assert::parameterType( 'string', $prefix, '$prefix' );
		Assert::parameter( strpos( $prefix, ':' ) === false, '$prefix', 'must not contain a colon' );
		Assert::parameterElementType( 'string', $prefixMapping, '$prefixMapping' );
		Assert::parameterElementType( 'string', array_keys( $prefixMapping ), 'array_keys( $prefixMapping )' );
		$this->prefix = $prefix;
		$this->prefixMapping = $prefixMapping;
		$this->idParser = $idParser;
	}

	/**
	 * Adds a fixed prefix to the serialization id and maps it according to the prefix mapping definition.
	 * Resulting id serialization is parsed as an EntityId.
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization ) {
		list( $repoName, $extraPrefixes, $relativeId ) = EntityId::splitSerialization( $idSerialization );
		if ( isset( $this->prefixMapping[$repoName] ) ) {
			$prefixedIdSerialization = EntityId::joinSerialization( [
				$this->prefixMapping[$repoName], $extraPrefixes, $relativeId
			] );
		} else {
			$prefixedIdSerialization = EntityId::joinSerialization( [ $this->prefix, '', $idSerialization ] );
		}
		return $this->idParser->parse( $prefixedIdSerialization );
	}

}
