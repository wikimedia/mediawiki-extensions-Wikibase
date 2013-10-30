<?php

namespace Wikibase\Lib\Serializers;

use Wikibase\LanguageFallbackChainFactory;

/**
 * Options for MultiLang Serializers.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class MultiLangSerializationOptions extends SerializationOptions {

	/**
	 * The language info array of the languages for which internationalized data (ie descriptions) should be returned.
	 * Or null for no restriction.
	 *
	 * Array keys are language codes (may include pseudo ones to identify some given fallback chains); values are
	 * LanguageFallbackChain objects (plain code inputs are constructed into language chains with a single language).
	 *
	 * @since 0.4
	 *
	 * @var null|array as described above
	 */
	protected $languages = null;

	/**
	 * Used to create LanguageFallbackChain objects when the old style array-of-strings argument is used in setLanguage().
	 *
	 * @var LanguageFallbackChainFactory
	 */
	protected $languageFallbackChainFactory;

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
			$this->languages = null;

			return;
		}

		$this->languages = array();

		foreach ( $languages as $languageCode => $languageFallbackChain ) {
			// back-compat
			if ( is_numeric( $languageCode ) ) {
				$languageCode = $languageFallbackChain;
				$languageFallbackChain = $this->getLanguageFallbackChainFactory()->newFromLanguageCode(
					$languageCode, LanguageFallbackChainFactory::FALLBACK_SELF
				);
			}

			$this->languages[$languageCode] = $languageFallbackChain;
		}
	}

	/**
	 * Gets the language codes of the languages for which internationalized data (ie descriptions) should be returned.
	 *
	 * @since 0.2
	 *
	 * @return array|null
	 */
	public function getLanguages() {
		if ( $this->languages === null ) {
			return null;
		} else {
			return array_keys( $this->languages );
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
		return $this->languages;
	}

	/**
	 * Get the language fallback chain factory previously set, or a new one if none was set.
	 *
	 * @since 0.4
	 *
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * Set language fallback chain factory and return the previously set one.
	 *
	 * @since 0.4
	 *
	 * @param LanguageFallbackChainFactory $factory
	 *
	 * @return LanguageFallbackChainFactory|null
	 */
	public function setLanguageFallbackChainFactory( LanguageFallbackChainFactory $factory ) {
		return wfSetVar( $this->languageFallbackChainFactory, $factory );
	}
}