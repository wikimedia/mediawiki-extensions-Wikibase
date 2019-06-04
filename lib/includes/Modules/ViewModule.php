<?php

namespace Wikibase;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class ViewModule extends ResourceLoaderModule {

	protected $targets = [ 'desktop' ];

	/**
	 * This one lets the client JavaScript know whether or not
	 * to enable tabs
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {

		$settings = Settings::singleton();

		if ( $settings->hasSetting( 'enableRefTabs' ) ) {
			echo 'here';
			// We're on a client (or at least the client configuration is available)
			$wbRefTags = [
				'url' => $settings->getSetting( 'enableRefTabs' )
			];
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRefTabsEnabled' => $wbRefTabsEnabled ] );
	}

}
