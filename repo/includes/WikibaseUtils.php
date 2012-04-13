<?php

/**
 * Utility functions for Wikibase.
 *
 * @since 0.1
 *
 * @file WikibaseUtils.php
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseUtils {

	/**
	 * Returns a list of language codes that Wikibase supports,
	 * ie the languages that a label or deswcription can be in.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getLanguageCodes() {
		static $languageCodes = null;

		if ( is_null( $languageCodes ) ) {
			$languageCodes = array_keys( Language::fetchLanguageNames() );
		}

		return $languageCodes;
	}

	/**
	 * Returns the identifiers of the sites supported by the Wikibase instance.
	 *
	 * @since 0.1
	 *
	 * @return array
	 */
	public static function getSiteIdentifiers() {
		return array(
			// TODO: figure out how to best do this.
			// Should probably be a setting, since we do not want to force WP stuff.
			// Still might be good to have a WP list somewhere.
			'en', 'de', 'nl'
		);
	}

}
