<?php

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class Scribunto_LuaWikibaseCapiuntoLibrary extends Scribunto_LuaLibraryBase {

	/**
	 * Register mw.wikibase.capiunto.lua library
	 *
	 * @since 0.5
	 *
	 * @return array
	 */
	public function register() {
		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.capiunto.lua', array(), array()
		);
	}

}

