<?php

namespace Wikibase\DataModel\Statement;

use Wikibase\DataModel\Statement\StatementGuidParsingException;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * @since 3.0
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class StatementGuidParser {

	/**
	 * @var EntityIdParser
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
	 *
	 * @return StatementGuid
	 * @throws StatementGuidParsingException
	 */
	public function parse( $serialization ) {
		if ( !is_string( $serialization ) ) {
			throw new StatementGuidParsingException( '$serialization must be a string; got ' . gettype( $serialization ) );
		}

		$keyParts = explode( StatementGuid::SEPARATOR, $serialization );

		if ( count( $keyParts ) !== 2 ) {
			throw new StatementGuidParsingException( '$serialization does not have the correct number of parts' );
		}

		try {
			return new StatementGuid( $this->entityIdParser->parse( $keyParts[0] ), $keyParts[1] );
		}
		catch( EntityIdParsingException $exception ) {
			throw new StatementGuidParsingException( '$serialization contains invalid EntityId: ' . $exception->getMessage() );
		}
	}

}
