<?php

declare( strict_types = 1 );

namespace Wikibase\Lib\Modules;

// phpcs:disable MediaWiki.Classes.FullQualifiedClassName -- T308814
use MediaWiki\ResourceLoader as RL;
use MediaWiki\ResourceLoader\ResourceLoader;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\WikibaseSettings;

/**
 * JavaScript variables needed to access the repo independent from the current
 * working wiki
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class RepoAccessModule extends RL\Module {

	/** @var string[] */
	protected $targets = [ 'desktop', 'mobile' ];

	/**
	 * This one lets the client JavaScript know where it can find
	 * the API and the article path of the repo
	 * @see RL\Module::getScript
	 *
	 * @param RL\Context $context
	 *
	 * @return string
	 */
	public function getScript( RL\Context $context ): string {
		global $wgServer, $wgScriptPath, $wgArticlePath;

		if ( WikibaseSettings::isClientEnabled() ) {
			$settings = WikibaseClient::getSettings();
			$wbRepo = [
				'url' => $settings->getSetting( 'repoUrl' ),
				'scriptPath' => $settings->getSetting( 'repoScriptPath' ),
				'articlePath' => $settings->getSetting( 'repoArticlePath' ),
			];
		} else {
			// just assume we're the repo
			$wbRepo = [
				'url' => $wgServer,
				'scriptPath' => $wgScriptPath,
				'articlePath' => $wgArticlePath,
			];
		}

		return ResourceLoader::makeConfigSetScript( [ 'wbRepo' => $wbRepo ] );
	}

}
