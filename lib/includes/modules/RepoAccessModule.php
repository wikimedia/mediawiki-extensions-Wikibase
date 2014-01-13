<?php

namespace Wikibase;
use ResourceLoaderModule, ResourceLoaderContext;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
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
			$variables = array(
				'wbRepoUrl' => $settings->getSetting( 'repoUrl' ),
				'wbRepoScriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'wbRepoArticlePath' => $settings->getSetting( 'repoArticlePath' )
			);
		} else {
			// Client configuration isn't available... just assume we're the repo
			$variables = array(
				'wbRepoUrl' => $wgServer,
				'wbRepoScriptPath' => $wgScriptPath,
				'wbRepoArticlePath' => $wgArticlePath
			);
		}

		return 'mediaWiki.config.set( ' . \FormatJson::encode( $variables ) . ' );';
	}
}
