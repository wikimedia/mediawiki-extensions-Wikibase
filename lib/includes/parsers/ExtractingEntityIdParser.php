<?php

namespace Wikibase\Lib\Parsers;

use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * EntityIdParser that parses full entity URIs into EntityIds.
 *
 * @since 0.5
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class ExtractingEntityIdParser implements EntityIdParser {

	public static function newFromBaseUri( $baseUri, EntityIdParser $idParser ) {
		$escapedUri = preg_quote( $baseUri, '!' );

		$escapedUri = preg_replace( '!^https?://!', 'https?://', $escapedUri );

		$uriRegexp =  '!^' . $escapedUri . '(.*)$!';

		return new ExtractingEntityIdParser( $uriRegexp, $idParser );
	}

	/**
	 * @var string
	 */
	private $uriRegexp;

	/**
	 * @var EntityIdParser
	 */
	private $idParser;

	/**
	 * @param string $uriRegexp Regular expression matching the entity ID as the first capture group.
	 * @param EntityIdParser $idParser
	 */
	public function __construct( $uriRegexp,  EntityIdParser $idParser ) {
		$this->idParser = $idParser;
		$this->uriRegexp = $uriRegexp;
	}

	/**
	 * @since 0.5
	 *
	 * @param string $entityUri
	 *
	 * @return EntityId
	 * @throws EntityIdParsingException
	 */
	public function parse( $entityUri ) {
		if ( preg_match ( $this->uriRegexp, $entityUri, $m ) ) {
			if ( !isset( $m[1] ) ) {
				throw new EntityIdParsingException( 'Bad entity URI (incomplete match): ' . $entityUri );
			}

			return $this->idParser->parse( $m[1] );
		}

		throw new EntityIdParsingException( 'Bad entity URI: ' . $entityUri );
	}

}
