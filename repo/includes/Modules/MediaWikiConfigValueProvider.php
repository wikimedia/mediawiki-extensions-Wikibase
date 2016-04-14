<?php

namespace Wikibase\Repo\Modules;

/**
 * Provider to pass information to mediaWiki.config.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Mättig
 * @author Jonas Kress
 */
interface MediaWikiConfigValueProvider {

	/**
	 * @return string Key for use in mediaWiki.config.
	 */
	public function getKey();

	/**
	 * @return mixed Non-complex value for use in mediaWiki.config.set, typically a string or
	 *  (nested) array of strings.
	 */
	public function getValue();

}
