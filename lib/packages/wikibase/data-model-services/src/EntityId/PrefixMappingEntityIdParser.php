<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\SerializableEntityId;
use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * EntityIdParser that maps a prefix of id serialization to a local prefix
 * according to the prefix mapping, or adds a fixed prefix to the id serialization
 * not containing a mapped prefix, and parses resulting string as an EntityId.
 * This can be used to prefix IDs of entities coming from a foreign repository
 * with the repository name to avoid clashes with IDs of local entities.
 *
 * @see docs/foreign-entity-ids.wiki in the DataModel module
 *
 * @since 3.7
 *
 * @license GPL-2.0-or-later
 */
class PrefixMappingEntityIdParser implements EntityIdParser {

	/**
	 * @var string[]
	 */
	private $prefixMapping;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @since 3.7
	 *
	 * @param string[] $prefixMapping Must contain an empty-string key defining prefix added to id serializations
	 *        that do not contain any of prefixed defined in $prefixMapping. Values should not contain colons,
	 *        in particular at the end of the string
	 * @param EntityIdParser $idParser
	 *
	 * @throws ParameterAssertionException
	 */
	public function __construct( array $prefixMapping, EntityIdParser $idParser ) {
		Assert::parameterElementType( 'string', $prefixMapping, '$prefixMapping' );
		Assert::parameterElementType( 'string', array_keys( $prefixMapping ), 'array_keys( $prefixMapping )' );
		Assert::parameter( isset( $prefixMapping[''] ), '$prefixMapping', 'must contain an empty-string key' );
		foreach ( $prefixMapping as $value ) {
			Assert::parameter(
				strpos( $value, ':' ) === false,
				'$prefixMapping',
				'must not contain strings containing colons'
			);
		}

		$this->prefixMapping = $prefixMapping;
		$this->idParser = $idParser;
	}

	/**
	 * Maps prefix(es) of the id serialization according to the prefix mapping definition, or adds a fixed prefix
	 * to the id serialization if there is no relevant prefix mapping,
	 * Resulting id serialization is parsed as an EntityId.
	 *
	 * @since 3.7
	 *
	 * @see docs/foreign-entity-ids.wiki in the DataModel module
	 *
	 * @param string $idSerialization
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $idSerialization ) {
		$defaultPrefix = $this->prefixMapping[''];
		list( $repoName, $extraPrefixes, $relativeId ) = SerializableEntityId::splitSerialization( $idSerialization );
		if ( $repoName !== '' && isset( $this->prefixMapping[$repoName] ) ) {
			$prefixedIdSerialization = SerializableEntityId::joinSerialization( [
				$this->prefixMapping[$repoName], $extraPrefixes, $relativeId,
			] );
		} else {
			$prefixedIdSerialization = SerializableEntityId::joinSerialization( [ $defaultPrefix, '', $idSerialization ] );
		}
		return $this->idParser->parse( $prefixedIdSerialization );
	}

}
