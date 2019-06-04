<?php

namespace Wikibase\Repo\Modules;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * JavaScript variables needed to change the view
 * // Temporary file, see: T199197
 *
 * @license GPL-2.0-or-later
 * @author M. Volz
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
			$wbRefTabsEnabled = $settings->getSetting( 'enableRefTabs' );
		} else {
			$wbRefTabsEnabled = false;
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRefTabsEnabled' => $wbRefTabsEnabled ] );
	}

}
