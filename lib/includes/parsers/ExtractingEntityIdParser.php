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

	/**
	 * @param string $baseUri A URI prefix for entities on the local repo, usually from the
	 *        conceptBaseUri config setting.
	 * @param EntityIdParser $idParser A parser for ID extracted from URIs by stripping the
	 *        $baseUri prefix.
	 *
	 * @return ExtractingEntityIdParser
	 */
	public static function newFromBaseUri( $baseUri, EntityIdParser $idParser ) {
		// NOTE: $baseUri usually comes from the conceptBaseUri setting, which is currently a
		// simple prefix. If support for placeholders like $1 is added to conceptBaseUri, this
		// needs to be considered here!
		$escapedUri = preg_quote( $baseUri, '!' );
		$uriRegexp =  '!^' . $escapedUri . '(.+)$!';

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
				// This happens if the $uriRegexp passed to the constructor does nto define a capture group.
				// Ideally, we would catch that issue in the constructor.
				throw new EntityIdParsingException( 'Bad entity URI (incomplete match): ' . $entityUri );
			}

			return $this->idParser->parse( $m[1] );
		}

		throw new EntityIdParsingException( 'Bad entity URI: ' . $entityUri );
	}

}
