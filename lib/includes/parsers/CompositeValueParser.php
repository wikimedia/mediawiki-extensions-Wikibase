<?php

namespace Wikibase\Lib\Parsers;

use InvalidArgumentException;
use ValueParsers\ParseException;
use ValueParsers\ValueParser;

/**
 * A generic value parser that forwards parsing to a list of other value parsers and returns the
 * result of the first parse attempt that succeeded.
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class CompositeValueParser implements ValueParser {

	/**
	 * @var ValueParser[]
	 */
	private $parsers;

	/**
	 * @var string !!!
	 */
	private $format;

	/**
	 * @param ValueParser[] $parsers !!!
	 * @param string $format !!!
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $parsers, $format ) {
		if ( empty( $parsers ) ) {
			throw new InvalidArgumentException( '!!!' );
		}

		$this->parsers = $parsers;
		$this->format = $format;
	}

	/**
	 * @param mixed $value
	 *
	 * @throws ParseException
	 * @return mixed
	 */
	public function parse( $value ) {
		foreach ( $this->parsers as $parser ) {
			try {
				return $parser->parse( $value );
			} catch ( ParseException $ex ) {
				continue;
			}
		}

		throw new ParseException( '!!!', $value, $this->format );
	}

}
