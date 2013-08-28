<?php

namespace Wikibase\Lib;

use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Parser that parses entity id strings into EntityId objects.
 *
 * @since 0.4
 *
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdParser extends StringValueParser {

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 *
	 * @return EntityId
	 * @throws ParseException
	 */
	protected function stringParse( $value ) {
		$idBuilders = BasicEntityIdParser::getBuilders();

		// TODO: extensions need to be able to add builders.
		// The construction of the actual id parser will thus need to be moved out.

		$parser = new \Wikibase\DataModel\Entity\EntityIdParser( $idBuilders );

		try {
			return $parser->parse( $value );
		}
		catch ( EntityIdParsingException $ex ) {
			throw new ParseException( '', 0, $ex );
		}
	}

}
