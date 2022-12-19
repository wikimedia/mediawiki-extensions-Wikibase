<?php

namespace Wikibase\Repo\Validators;

use InvalidArgumentException;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * UrlValidator checks URLs based on sub-validators for each scheme.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class UrlValidator implements ValueValidator {

	/**
	 * @var ValueValidator[]
	 */
	private $validators;

	/**
	 * Constructs a UrlValidator that checks the given URL schemes.
	 *
	 * @param ValueValidator[] $validators a map of scheme names (e.g. 'html') to ValueValidators.
	 *        The special scheme name "*" can be used to specify a validator for
	 *        other schemes. You may want to use UrlSchemaValidators to create the
	 *        respective validators conveniently.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( array $validators ) {
		foreach ( $validators as $scheme => $validator ) {
			if ( !is_string( $scheme ) ) {
				throw new InvalidArgumentException( 'The keys in $validators must be strings (scheme names).' );
			}

			if ( !( $validator instanceof ValueValidator ) ) {
				throw new InvalidArgumentException( 'The values in $validators must be instances of ValueValidator.' );
			}
		}

		$this->validators = $validators;
	}

	/**
	 * @see ValueValidator::validate()
	 *
	 * @param string $url
	 *
	 * @throws InvalidArgumentException
	 * @return Result
	 */
	public function validate( $url ) {
		if ( !is_string( $url ) ) {
			throw new InvalidArgumentException( '$url must be a string.' );
		}

		// See RFC 3986, section-3.1.
		if ( !preg_match( '/^([-+.a-z\d]+):/i', $url, $matches ) ) {
			return Result::newError( [
				Error::newError( 'Malformed URL, can\'t find scheme name.', null, 'url-scheme-missing', [ $url ] ),
			] );
		}

		// Should we also check for and fail on whitespace in $value?

		$scheme = strtolower( $matches[1] );

		if ( isset( $this->validators[$scheme] ) ) {
			$validator = $this->validators[$scheme];
		} elseif ( isset( $this->validators['*'] ) ) {
			$validator = $this->validators['*'];
		} else {
			return Result::newError( [
				Error::newError( 'Unsupported URL scheme', null, 'bad-url-scheme', [ $scheme ] ),
			] );
		}

		return $validator->validate( $url );
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}

}
