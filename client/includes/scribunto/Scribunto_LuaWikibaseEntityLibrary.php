<?php

use Wikibase\Client\Scribunto\WikibaseLuaEntityBindings;
use Wikibase\Settings;

/**
 * Registers and defines functions to access Wikibase through the Scribunto extension
 *
 * @since 0.5
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

class Scribunto_LuaWikibaseEntityLibrary extends Scribunto_LuaLibraryBase {

	/* @var WikibaseLuaEntityBindings */
	protected $wbLibrary;

	/**
	 * Constructor for wrapper class, initialize member object holding implementation
	 *
	 * @param Scribunto_LuaEngine $engine
	 * @since 0.5
	 */
	public function __construct( $engine ) {
		$this->wbLibrary = new WikibaseLuaEntityBindings(
			Settings::get( 'siteGlobalID' )
		);

		parent::__construct( $engine );
	}

	/**
	 * Register mw.wikibase.lua library
	 *
	 * @since 0.5
	 */
	public function register() {
		$lib = array(
			'getGlobalSiteId' => array( $this, 'getGlobalSiteId' ),
		);

		return $this->getEngine()->registerInterface(
			__DIR__ . '/mw.wikibase.entity.lua', $lib, array()
		);
	}

	/**
	 * Wrapper for getGlobalSiteId in Scribunto_LuaWikibaseLibraryImplementation
	 *
	 * @since 0.5
	 *
	 */
	public function getGlobalSiteId() {
		return $this->wbLibrary->getGlobalSiteId();
	}
}
