<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;

/**
 * Options for Serializer objects.
 *
 * TODO: use PDO like options system as done in ValueParsers
 *
 * @since 0.2
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class SerializationOptions {

	/**
	 * @since 0.3
	 * @var boolean
	 */
	protected $indexTags = false;

	const ID_KEYS_UPPER = 1;
	const ID_KEYS_LOWER = 2;
	const ID_KEYS_BOTH = 3;

	/**
	 * @since 0.5
	 * @var int $idKeyMode bit field determining whether to use upper case entities IDs as
	 *      keys in the serialized structure, or lower case IDs, or both.
	 */
	protected $idKeyMode = self::ID_KEYS_UPPER;

	/**
	 * Sets if tags should be indexed.
	 * The MediaWiki API needs this when building API results in formats such as XML.
	 *
	 * @since 0.3
	 *
	 * @param boolean $indexTags
	 *
	 * @throws InvalidArgumentException
	 */
	public function setIndexTags( $indexTags ) {
		if ( !is_bool( $indexTags ) ) {
			throw new InvalidArgumentException( 'Expected boolean, got something else' );
		}

		$this->indexTags = $indexTags;
	}

	/**
	 * Returns if tags should be indexed.
	 *
	 * @since 0.3
	 *
	 * @return boolean
	 */
	public function shouldIndexTags() {
		return $this->indexTags;
	}

	/**
	 * Returns whether lower case entities IDs should be used as keys in the serialized data structure.
	 *
	 * @see setIdKeyMode()
	 *
	 * @since 0.5
	 *
	 * @return boolean
	 */
	public function shouldUseLowerCaseIdsAsKeys() {
		return ( $this->idKeyMode & self::ID_KEYS_LOWER ) > 0;
	}

	/**
	 * Returns whether upper case entities IDs should be used as keys in the serialized data structure.
	 *
	 * @see setIdKeyMode()
	 *
	 * @since 0.5
	 *
	 * @return boolean
	 */
	public function shouldUseUpperCaseIdsAsKeys() {
		return ( $this->idKeyMode & self::ID_KEYS_UPPER ) > 0;
	}

	/**
	 * Sets whether upper case entities IDs should be used as keys in the serialized data structure,
	 * or lower case, or both.
	 *
	 * Allowing for different forms of IDs to be used as keys is needed for backwards
	 * compatibility while we change from lower case to upper case IDs in version 0.5.
	 *
	 * @see shouldUseLowerCaseIdsAsKeys()
	 * @see shouldUseUpperCaseIdsAsKeys()
	 *
	 * @since 0.5
	 *
	 * @param int $mode a bit field using the ID_KEYS_XXX constants.
	 * @throws \InvalidArgumentException
	 */
	public function setIdKeyMode( $mode ) {
		if ( ( $mode & self::ID_KEYS_BOTH ) === 0 ) {
			throw new \InvalidArgumentException( "At least one ID key mode must be set in the bit field." );
		}

		if ( ( $mode & ~self::ID_KEYS_BOTH ) !== 0 ) {
			throw new \InvalidArgumentException( "Unknown bits set in ID key mode, use the ID_KEYS_XXX constants." );
		}

		$this->idKeyMode = $mode;
	}
}