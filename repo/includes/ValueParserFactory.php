<?php

namespace Wikibase\Repo;

use InvalidArgumentException;
use LogicException;
use OutOfBoundsException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;

/**
 * Builds ValueParser objects
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ValueParserFactory {

	/**
	 * Maps parser id to ValueParser class or builder callback.
	 *
	 * @var callable[]
	 */
	private $parsers = [];

	/**
	 * @param callable[] $valueParsers An associative array mapping parser ids to
	 *        factory functions.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $valueParsers ) {
		foreach ( $valueParsers as $parserId => $parserBuilder ) {
			if ( !is_string( $parserId ) ) {
				throw new InvalidArgumentException( 'Parser id needs to be a string' );
			}

			if ( !is_callable( $parserBuilder ) ) {
				throw new InvalidArgumentException( 'Parser class needs to be a callable' );
			}

			$this->parsers[$parserId] = $parserBuilder;
		}
	}

	/**
	 * Returns the ValueParser identifiers.
	 *
	 * @return string[]
	 */
	public function getParserIds() {
		return array_keys( $this->parsers );
	}

	/**
	 * Returns an instance of the ValueParser with the provided id or null if there is no such ValueParser.
	 *
	 * @param string $parserId
	 * @param ParserOptions $parserOptions
	 *
	 * @throws OutOfBoundsException If no parser was registered for $parserId
	 * @return ValueParser
	 */
	public function newParser( $parserId, ParserOptions $parserOptions ) {
		if ( !array_key_exists( $parserId, $this->parsers ) ) {
			throw new OutOfBoundsException( "No builder registered for parser ID $parserId" );
		}

		$builder = $this->parsers[$parserId];
		$parser = $this->instantiateParser( $builder, $parserOptions );

		return $parser;
	}

	/**
	 * @param callable $builder A callable that returns a ValueParser.
	 *        $options will be passed to the constructor or callable, respectively.
	 * @param ParserOptions $options
	 *
	 * @throws LogicException if the builder did not create a ValueParser
	 * @return ValueParser
	 */
	private function instantiateParser( $builder, ParserOptions $options ) {
		$parser = call_user_func( $builder, $options );

		if ( !( $parser instanceof ValueParser ) ) {
			throw new LogicException( "Invalid parser builder, did not create an instance of ValueParser." );
		}

		return $parser;
	}

}
