<?php

namespace Wikibase;

use ResourceLoaderContext;
use ResourceLoaderModule;
use Xml;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @since 0.4
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
	 * @since 0.4
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
			$wbRepo = array(
				'url' => $settings->getSetting( 'repoUrl' ),
				'scriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'articlePath' => $settings->getSetting( 'repoArticlePath' )
			);
		} else {
			// Client configuration isn't available... just assume we're the repo
			$wbRepo = array(
				'url' => $wgServer,
				'scriptPath' => $wgScriptPath,
				'articlePath' => $wgArticlePath
			);
		}

		return Xml::encodeJsCall( 'mediaWiki.config.set', array( 'wbRepo', $wbRepo ) );
	}

}
