<?php

declare( strict_types = 1 );

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
	public function __construct( string $prefix, EntityIdParser $idParser ) {
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
	 */
	public function parse( string $idSerialization ): EntityId {
		if ( !str_starts_with( $idSerialization, $this->prefix ) ) {
			throw new EntityIdParsingException( 'Missing expected prefix "' . $this->prefix
				. '" in "' . $idSerialization . '"' );
		}

		return $this->idParser->parse( substr( $idSerialization, strlen( $this->prefix ) ) );
	}

}
