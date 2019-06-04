<?php

namespace Wikibase;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * JavaScript variables needed to change the view
 *
 * @license GPL-2.0-or-later
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

		global $wbRefTabsEnabled;

		$settings = Settings::singleton();

		if ( $settings->hasSetting( 'enableRefTabs' ) ) {
			$wbRefTabsEnabled = $settings->getSetting( 'enableRefTabs' );
		} else {
			$wbRefTabsEnabled = false;
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRefTabsEnabled' => $wbRefTabsEnabled ] );
	}

}
