<?php

namespace Wikibase\DataModel\Services\EntityId;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * EntityIdParser that strips a fixed prefix and parses the remaining suffix as an EntityId.
 * This can be used to parse entity URIs into EntityId objects.
 *
 * @since 1.1
 *
 * @license GPL-2.0-or-later
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
	 * @param string $prefix The prefix to be stripped. Stripping is case sensitive.
	 * @param EntityIdParser $idParser
	 */
	public function __construct( $prefix, EntityIdParser $idParser ) {
		$this->prefix = $prefix;
		$this->idParser = $idParser;
	}

	/**
	 * Parses the given string into an EntityId by first stripping a fixed prefix.
	 * If the string does not start with the expected prefix, an EntityIdParsingException
	 * is thrown.
	 *
	 * @param string $idSerialization An entity ID with some prefix attached, e.g. an entity URI.
	 *
	 * @throws EntityIdParsingException If the string does not start with the expected prefix,
	 *         or the remaining suffix is not a valid entity ID string.
	 * @return EntityId
	 */
	public function parse( $idSerialization ) {
		$prefixLength = strlen( $this->prefix );

		if ( strncmp( $idSerialization, $this->prefix, $prefixLength ) !== 0 ) {
			throw new EntityIdParsingException( 'Missing expected prefix "' . $this->prefix
				. '" in "' . $idSerialization . '"' );
		}

		$idSerialization = substr( $idSerialization, $prefixLength );
		return $this->idParser->parse( $idSerialization );
	}

}
