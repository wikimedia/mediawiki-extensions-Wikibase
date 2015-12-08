<?php

namespace Wikibase\Repo\Parsers;

use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParsingException;
use Wikibase\DataModel\Entity\EntityIdValue;

/**
 * Parser that parses entity id strings into EntityIdValue objects.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdValueParser extends StringValueParser {

	const FORMAT_NAME = 'entity-id-value';

	/**
	 * @var EntityIdParser
	 */
	private $parser;

	/**
	 * @param EntityIdParser $parser
	 */
	public function __construct( EntityIdParser $parser ) {
		parent::__construct();

		$this->parser = $parser;
	}

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 *
	 * @throws ParseException
	 * @return EntityIdValue
	 */
	protected function stringParse( $value ) {
		try {
			return new EntityIdValue( $this->parser->parse( $value ) );
		} catch ( EntityIdParsingException $ex ) {
			throw new ParseException(
				$ex->getMessage(),
				$value,
				self::FORMAT_NAME
			);
		}
	}

}
