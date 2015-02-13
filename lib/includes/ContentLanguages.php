<?php

namespace Wikibase\Lib;

/**
 * A list of languages supported as content language
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 * @author Marius Hoch < hoo@online.de >
 */
interface ContentLanguages {

	/**
	 * @return string[] Array of language codes supported as content language
	 */
	public function getLanguages();

	/**
	 * @param string $languageCode
	 *
	 * @return bool
	 */
	public function hasLanguage( $languageCode );

	/**
	 * Get the name of the language specified by $languageCode. The name should be in the language
	 * specified by $inLanguage, but it might be in any other language. If null is given as $inLanguage,
	 * $languageCode is used, i. e. the service tries to give the autonym of the language.
	 *
	 * @param string $languageCode
	 * @param string|null $inLanguage
	 *
	 * @return string
	 */
	public function getName( $languageCode, $inLanguage = null );

}
