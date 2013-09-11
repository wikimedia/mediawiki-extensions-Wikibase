<?php

namespace Wikibase\Lib;

use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParsingException;

/**
 * Parser that parses entity id strings into EntityId objects.
 *
 * TODO: this should be turned into a proper adapter using a DataModel EntityIdParser
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

		$parser = new DispatchingEntityIdParser( $idBuilders );

		try {
			return $parser->parse( $value );
		}
		catch ( EntityIdParsingException $ex ) {
			throw new ParseException( '', 0, $ex );
		}
	}

}
