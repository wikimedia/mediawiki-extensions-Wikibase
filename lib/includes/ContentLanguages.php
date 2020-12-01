<?php

namespace Wikibase\Lib;

/**
 * A list of languages supported as content language
 *
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Marius Hoch < hoo@online.de >
 * @license GPL-2.0-or-later
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

}
