<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;

/**
 * EntityIdParser that strips a fixed prefix and parses the remaining suffix as an EntityId.
 * This can be used to parse entity URIs into EntityId objects.
 *
 * @since 1.1
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class SuffixEntityIdParser implements EntityIdParser {

	/**
	 * @var string
	 */
	private $prefix;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param string $prefix The prefix to be stripped. Stripping is cases sensitive.
	 * @param EntityIdParser $idParser
	 */
	public function __construct( $prefix, EntityIdParser $idParser ) {
		$this->prefix = $prefix;
		$this->idParser = $idParser;
	}

	/**
	 * Parses the given $prefixedEntityId into an EntityId by first stripping a fixed prefix.
	 * If $prefixedEntityId does nto start with the expected prefix, a EntityIdParsingException
	 * is thrown.
	 *
	 * @param string $prefixedEntityId An EntityId with some prefix attached, e.g. an entity URI.
	 *
	 * @throws EntityIdParsingException If $prefixedEntityId doesn't start with the expected prefix,
	 *         or the remaining suffix is not a valid entity ID string.
	 *
	 * @return EntityId
	 */
	public function parse( $prefixedEntityId ) {
		if ( strncmp( $this->prefix, $prefixedEntityId, strlen( $this->prefix ) ) === 0 ) {
			$suffix = substr( $prefixedEntityId, strlen( $this->prefix ) );

			return $this->idParser->parse( $suffix );
		}

		throw new EntityIdParsingException( "Missing expected prefix `{$this->prefix}` in `{$prefixedEntityId}`" );
	}

}
