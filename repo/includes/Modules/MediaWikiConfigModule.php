<?php

namespace Wikibase\Repo\Modules;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * Generic, reusable ResourceLoader module to set a JavaScript configuration variable via
 * mw.config.set.
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class MediaWikiConfigModule extends ResourceLoaderModule {

	/**
	 * @var callable
	 */
	private $getConfigValueProvider;

	/**
	 * @param array $options ResourceLoader module options. Must include a "getconfigvalueprovider"
	 *  callable that returns a MediaWikiConfigValueProvider when called.
	 */
	public function __construct( array $options ) {
		$this->getConfigValueProvider = $options['getconfigvalueprovider'];
	}

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string JavaScript code
	 */
	public function getScript( ResourceLoaderContext $context ) {
		/** @var MediaWikiConfigValueProvider $configValueProvider */
		$configValueProvider = call_user_func( $this->getConfigValueProvider );

		return ResourceLoader::makeConfigSetScript( [
			$configValueProvider->getKey() => $configValueProvider->getValue()
		] );
	}

	/**
	 * @return bool Always true.
	 */
	public function enableModuleContentVersion() {
		return true;
	}

}
