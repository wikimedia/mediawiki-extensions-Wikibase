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
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class ValueParserFactory {

	/**
	 * Maps parser id to ValueParser class or builder callback.
	 *
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $parsers = array();

	/**
	 * @since 0.1
	 *
	 * @param string|callable[] $valueParsers An associative array mapping parser ids to
	 *        class names or callable builders.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $valueParsers ) {
		foreach ( $valueParsers as $parserId => $parserBuilder ) {
			if ( !is_string( $parserId ) ) {
				throw new InvalidArgumentException( 'Parser id needs to be a string' );
			}

			if ( !is_string( $parserBuilder ) && !is_callable( $parserBuilder ) ) {
				throw new InvalidArgumentException( 'Parser class needs to be a class name or callable' );
			}

			$this->parsers[$parserId] = $parserBuilder;
		}
	}

	/**
	 * Returns the ValueParser identifiers.
	 *
	 * @since 0.1
	 *
	 * @return string[]
	 */
	public function getParserIds() {
		return array_keys( $this->parsers );
	}

	/**
	 * Returns the parser builder (class name or callable) for $parserId, or null if
	 * no builder was registered for that id.
	 *
	 * @since 0.1
	 *
	 * @param string $parserId
	 *
	 * @return string|callable|null
	 */
	public function getParserBuilder( $parserId ) {
		if ( array_key_exists( $parserId, $this->parsers ) ) {
			return $this->parsers[$parserId];
		}

		return null;
	}

	/**
	 * Returns an instance of the ValueParser with the provided id or null if there is no such ValueParser.
	 *
	 * @since 0.1
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
	 * @param string|callable $builder Either a classname of an implementation of ValueParser,
	 *        or a callable that returns a ValueParser. $options will be passed to the constructor
	 *        or callable, respectively.
	 * @param ParserOptions $options
	 *
	 * @throws LogicException if the builder did not create a ValueParser
	 * @return ValueParser
	 */
	private function instantiateParser( $builder, ParserOptions $options ) {
		if ( is_string( $builder ) ) {
			$parser = new $builder( $options );
		} else {
			$parser = call_user_func( $builder, $options );
		}

		if ( !( $parser instanceof ValueParser ) ) {
			throw new LogicException( "Invalid parser builder, did not create an instance of ValueParser." );
		}

		return $parser;
	}

}
