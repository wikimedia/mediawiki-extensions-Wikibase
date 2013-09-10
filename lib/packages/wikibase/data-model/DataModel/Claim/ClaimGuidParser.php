<?php

namespace Wikibase\DataModel\Claim;

use Wikibase\DataModel\Claim\ClaimGuid;
use Wikibase\DataModel\Claim\ClaimGuidParsingException;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;

/**
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class ClaimGuidParser {

	/**
	 * @var DispatchingEntityIdParser $entityIdParser
	 */
	private $entityIdParser;

	/**
	 * @param EntityIdParser $entityIdParser
	 */
	public function __construct( EntityIdParser $entityIdParser ) {
		$this->entityIdParser = $entityIdParser;
	}

	/**
	 * @param string $serialization
	 * @return ClaimGuid
	 * @throws ClaimGuidParsingException
	 */
	public function parse( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new ClaimGuidParsingException( '$serialization needs to be a string' );
		}

		$keyParts = explode( ClaimGuid::SEPARATOR, $serialization );

		if ( count( $keyParts ) !== 2 ) {
			throw new ClaimGuidParsingException( '$serialization does not have the correct number of parts' );
		}

		return new ClaimGuid( $this->entityIdParser->parse( $keyParts[0] ), $keyParts[1] );
	}

}