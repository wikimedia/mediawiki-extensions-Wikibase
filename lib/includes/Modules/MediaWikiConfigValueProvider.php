<?php

namespace Wikibase\Lib\Modules;

/**
 * Provider to pass information to mw.config.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 * @author Thiemo Kreuz
 * @author Jonas Kress
 */
interface MediaWikiConfigValueProvider {

	/**
	 * @return string Key for use in mw.config.
	 */
	public function getKey();

	/**
	 * @return mixed Non-complex value for use in mw.config.set, typically a string or
	 *  (nested) array of strings.
	 */
	public function getValue();

}
