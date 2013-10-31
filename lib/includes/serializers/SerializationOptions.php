<?php

namespace Wikibase\Lib\Serializers;

use InvalidArgumentException;
use ValueFormatters\ValueFormatter;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\PropertyDataTypeLookup;

/**
 * Options for Serializer objects.
 *
 * @since 0.2
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Liangent
 */
class SerializationOptions {

	/**
	 * @since 0.5
	 * @const key for the entityIdKeyMode option, a  bit field determining whether to use
	 *        upper case entities IDs as keys in the serialized structure, or lower case
	 *        IDs, or both.
	 */
	const OPT_ID_KEY_MODE = 'entityIdKeyMode';

	const ID_KEYS_UPPER = 1;
	const ID_KEYS_LOWER = 2;
	const ID_KEYS_BOTH = 3;

	/**
	 * @since 0.5
	 * @const key for the indexTags option, a boolean indicating whether associative or indexed
	 *        arrays should be used for output. This allows indexed mode to be forced for used
	 *        with ApiResults in XML model.
	 */
	const OPT_INDEX_TAGS = 'indexTags';

	/**
	 * @const Option key for the language fallback chains to apply. The value must be an array.
	 * Array keys are language codes (may include pseudo ones to identify some given fallback chains); values are
	 * LanguageFallbackChain objects (plain code inputs are constructed into language chains with a single language).
	 *
	 * @since 0.5
	 */
	const OPT_LANGUAGES = 'languages';

	/**
	 * @const Option key for a LanguageFallbackChainFactory object
	 * used to create LanguageFallbackChain objects when the old style array-of-strings
	 * argument is used in setLanguage().
	 *
	 * @since 0.5
	 */
	const OPT_LANGUAGE_FALLBACK_CHAIN_FACTORY = 'languageFallbackChainFactory';

	/**
	 * @var array
	 */
	protected $options = array();

	/**
	 * @since 0.5
	 *
	 * @param array $options
	 */
	public function __construct( array $options = array() ) {
		$this->setOptions( $options );

		$this->initOption( self::OPT_ID_KEY_MODE, self::ID_KEYS_UPPER );
		$this->initOption( self::OPT_INDEX_TAGS, false );
	}

	protected function checkKey( $key) {
		if ( !is_string( $key ) ) {
			throw new \InvalidArgumentException( 'option keys must be strings' );
		}

		if ( !preg_match( '/^[-.\/:_+*!$#@0-9a-zA-Z]+$/', $key ) ) {
			throw new \InvalidArgumentException( 'malformed option key: ' . $key );
		}
	}

	/**
	 * Sets the given option if it is not already set.
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function initOption( $key, $value ) {
		$this->checkKey( $key );

		if ( !array_key_exists( $key, $this->options ) && $value !== null ) {
			$this->options[$key] = $value;
		}
	}

	/**
	 * Sets the given option.
	 * Setting an option to null is equivalent to removing it.
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function setOption( $key, $value ) {
		$this->checkKey( $key );

		if ( $value === null ) {
			unset( $this->options[$key] );
		} else {
			$this->options[$key] = $value;
		}
	}

	/**
	 * Returns whether the given option is set.
	 *
	 * @param $key
	 *
	 * @since 0.5
	 *
	 * @return bool
	 */
	public function hasOption( $key ) {
		return isset( $this->options[$key] );
	}

	/**
	 * Adds an entry to an option that is defined to be a list.
	 * If the option specified is not a list, an error is raised.
	 * If the given value is already in the list, this method has no effect.
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @throws \RuntimeException
	 */
	public function addToOption( $key, $value ) {
		if ( !isset( $this->options[$key] ) ) {
			$this->options[$key] = array();
		}

		if ( !is_array( $this->options[$key] ) ) {
			throw new \RuntimeException( 'option ' . $key . ' is not a list!' );
		}

		if ( !in_array( $value, $this->options[$key] ) ) {
			$this->options[$key][] = $value;
		}
	}

	/**
	 * Removes an entry from an option that is defined to be a list.
	 * If the option specified is not a list, an error is raised.
	 * If the given value is not in the list, this method has no effect.
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @throws \RuntimeException
	 */
	public function removeFromOption( $key, $value ) {
		if ( !isset( $this->options[$key] ) ) {
			return; //nothing to do.
		}

		if ( !is_array( $this->options[$key] ) ) {
			throw new \RuntimeException( 'option ' . $key . ' is not a list!' );
		}

		if ( in_array( $value, $this->options[$key] ) ) {
			$oldList = $this->options[$key];
			$newList = array_diff( $oldList, array( $value ) );
			$this->options[$key] = $newList;
		}
	}

	/**
	 * Returns the given option.
	 *
	 * @since 0.5
	 *
	 * @param string $key
	 * @param mixed $default used if the option wasn't set.
	 *
	 * @return mixed
	 */
	public function getOption( $key, $default = null ) {
		if ( array_key_exists( $key, $this->options ) ) {
			return $this->options[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Sets the given options in this options object.
	 *
	 * @since 0.5
	 *
	 * @param array $options associative array of options
	 */
	public function setOptions( array $options ) {
		foreach ( $options as $key => $value) {
			$this->setOption( $key, $value );
		}
	}

	/**
	 * Returns the options set in this SerializationOptions object
	 * as an associative array.
	 *
	 * The array returned by this method is a copy of the internal data structure.
	 * Manipulating that array has no impact on this SerializationOptions object.
	 *
	 * @since 0.5
	 *
	 * @return array the options
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Merges the given options into this options object.
	 * Options set in $options will override options already present in this options object.
	 *
	 * Shorthand for $this->setOptions( $options->getOptions() );
	 *
	 * @since 0.5
	 *
	 * @param SerializationOptions $options
	 */
	public function merge( SerializationOptions $options ) {
		$this->setOptions( $options->getOptions() );
	}

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

		$this->setOption( self::OPT_INDEX_TAGS, $indexTags );
	}

	/**
	 * Returns if tags should be indexed.
	 *
	 * @since 0.3
	 *
	 * @return boolean
	 */
	public function shouldIndexTags() {
		return $this->getOption( self::OPT_INDEX_TAGS );
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
		$idKeyMode = $this->getOption( self::OPT_ID_KEY_MODE );
		return ( $idKeyMode & self::ID_KEYS_LOWER ) > 0;
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
		$idKeyMode = $this->getOption( self::OPT_ID_KEY_MODE );
		return ( $idKeyMode & self::ID_KEYS_UPPER ) > 0;
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

		$this->setOption( self::OPT_ID_KEY_MODE, $mode );
	}

	/**
	 * Sets the language codes or language fallback chains of the languages for which internationalized data
	 * (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @param array|null $languages array of strings (back compat, as language codes)
	 *                     or LanguageFallbackChain objects (requested language codes as keys, to identify chains)
	 */
	public function setLanguages( array $languages = null ) {
		if ( $languages === null ) {
			$this->setOption( self::OPT_LANGUAGES, null );
			return;
		}

		$chains = array();

		foreach ( $languages as $languageCode => $languageFallbackChain ) {
			// back-compat
			if ( is_numeric( $languageCode ) ) {
				$languageCode = $languageFallbackChain;
				$languageFallbackChain = $this->getLanguageFallbackChainFactory()->newFromLanguageCode(
					$languageCode, LanguageFallbackChainFactory::FALLBACK_SELF
				);
			}

			$chains[$languageCode] = $languageFallbackChain;
		}

		$this->setOption( self::OPT_LANGUAGES, $chains );
	}

	/**
	 * Gets the language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @return array|null
	 */
	public function getLanguages() {
		$languages = $this->getLanguageFallbackChains();

		if ( $languages === null ) {
			return null;
		} else {
			return array_keys( $languages );
		}
	}

	/**
	 * Gets an associative array with language codes as keys and their fallback chains as values, or null.
	 *
	 * @since 0.4
	 *
	 * @return array|null
	 */
	public function getLanguageFallbackChains() {
		return $this->getOption( self::OPT_LANGUAGES );
	}

	/**
	 * Get the language fallback chain factory previously set, or a new one if none was set.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		$factory = $this->getOption( self::OPT_LANGUAGE_FALLBACK_CHAIN_FACTORY );

		if ( $factory === null ) {
			$factory = new LanguageFallbackChainFactory();
			$this->setLanguageFallbackChainFactory( $factory );
		}

		return $factory;
	}

	/**
	 * Set language fallback chain factory and return the previously set one.
	 *
	 * @since 0.4
	 *
	 * @param LanguageFallbackChainFactory $factory
	 */
	public function setLanguageFallbackChainFactory( LanguageFallbackChainFactory $factory ) {
		$this->setOption( self::OPT_LANGUAGE_FALLBACK_CHAIN_FACTORY, $factory );
	}
}
