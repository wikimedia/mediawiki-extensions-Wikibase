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
class RepoAccessModule extends ResourceLoaderModule {

	/**
	 * This one lets the client JavaScript know where it can find
	 * the API and the article path of the repo
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		global $wgServer, $wgScriptPath, $wgArticlePath;

		$settings = Settings::singleton();

		if ( $settings->hasSetting( 'repoUrl' ) ) {
			// We're on a client (or at least the client configuration is available)
			$wbRepo = [
				'url' => $settings->getSetting( 'repoUrl' ),
				'scriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'articlePath' => $settings->getSetting( 'repoArticlePath' )
			];
		} else {
			// Client configuration isn't available... just assume we're the repo
			$wbRepo = [
				'url' => $wgServer,
				'scriptPath' => $wgScriptPath,
				'articlePath' => $wgArticlePath
			];
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRepo' => $wbRepo ] );
	}

}
