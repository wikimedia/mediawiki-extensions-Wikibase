<?php

namespace Wikibase\DataModel\Assert;

use Wikimedia\Assert\Assert;
use Wikimedia\Assert\ParameterAssertionException;

/**
 * Provides functions to assure values are allowable repository
 * names in Wikibase.
 *
 * @see docs/foreign-entity-ids.wiki
 * @see \Wikimedia\Assert\Assert
 *
 * @since 6.3
 *
 * @license GPL-2.0-or-later
 */
class RepositoryNameAssert {

	/**
	 * @since 6.3
	 *
	 * @param string $value The actual value of the parameter
	 * @param string $name The name of the parameter being checked
	 *
	 * @throws ParameterAssertionException If $value is not a valid repository name.
	 */
	public static function assertParameterIsValidRepositoryName( $value, $name ) {
		if ( !self::isValidRepositoryName( $value ) ) {
			throw new ParameterAssertionException( $name, 'must be a string not including colons nor periods' );
		}
	}

	/**
	 * @since 6.3
	 *
	 * @param array $values The actual value of the parameter. If this is not an array,
	 *        a ParameterTypeException is thrown.
	 * @param string $name The name of the parameter being checked
	 *
	 * @throws ParameterAssertionException If any element of $values is not
	 *         a valid repository name.
	 */
	public static function assertParameterKeysAreValidRepositoryNames( $values, $name ) {
		Assert::parameterType( 'array', $values, $name );
		// TODO: change to Assert::parameterKeyType when the new version of the library is released
		Assert::parameterElementType( 'string', array_keys( $values ), "array_keys( $name )" );

		foreach ( array_keys( $values ) as $key ) {
			if ( !self::isValidRepositoryName( $key ) ) {
				throw new ParameterAssertionException(
					"array_keys( $name )",
					'must not contain strings including colons or periods'
				);
			}
		}
	}

	/**
	 * @param string $value
	 * @return bool
	 */
	private static function isValidRepositoryName( $value ) {
		return is_string( $value ) && strpos( $value, ':' ) === false && strpos( $value, '.' ) === false;
	}

}
