<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Wikibase\Lib\WikibaseSettings;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class RepoAccessModule extends ResourceLoaderModule {

	protected $targets = [ 'desktop', 'mobile' ];

	/**
	 * This one lets the client JavaScript know where it can find
	 * the API and the article path of the repo
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ): string {
		global $wgServer, $wgScriptPath, $wgArticlePath;

		if ( WikibaseSettings::isClientEnabled() ) {
			$settings = WikibaseSettings::getClientSettings();
			$wbRepo = [
				'url' => $settings->getSetting( 'repoUrl' ),
				'scriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'articlePath' => $settings->getSetting( 'repoArticlePath' )
			];
		} else {
			// just assume we're the repo
			$wbRepo = [
				'url' => $wgServer,
				'scriptPath' => $wgScriptPath,
				'articlePath' => $wgArticlePath
			];
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRepo' => $wbRepo ] );
	}

}
