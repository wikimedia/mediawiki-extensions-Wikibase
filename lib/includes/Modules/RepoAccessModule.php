<?php

namespace Wikibase;

use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @license GPL-2.0+
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

		if ( WikibaseSettings::isClientEnabled() ) {
			$settings = WikibaseSettings::getClientSettings();
			$wbRepo = array(
				'url' => $settings->getSetting( 'repoUrl' ),
				'scriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'articlePath' => $settings->getSetting( 'repoArticlePath' )
			);
		} else {
			// Client isn't enabled... assume we're on the repo.
			$wbRepo = array(
				'url' => $wgServer,
				'scriptPath' => $wgScriptPath,
				'articlePath' => $wgArticlePath
			);
		}

		return ResourceLoader::makeConfigSetScript( array( 'wbRepo' => $wbRepo ) );
	}

}
