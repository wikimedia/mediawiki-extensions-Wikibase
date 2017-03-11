<?php

namespace Wikibase;

use FormatJson;
use MediaWikiSite;
use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Wikibase\Client\WikibaseClient;

/**
 * Provides the {{WBREPONAME}} magic word for mediawiki.jqueryMsg
 *
 * @license GPL-2.0+
 * @author Roan Kattouw < roan.kattouw@gmail.com >
 */
class JQueryMsgModule extends ResourceLoaderModule {

	/**
	 * @see ResourceLoaderModule::getScript
	 *
	 * @param ResourceLoaderContext $context
	 *
	 * @return string
	 */
	public function getScript( ResourceLoaderContext $context ) {
		// TODO duplicated from WikibaseClientHooks, factor out
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();
		$repoSiteName = $settings->getSetting( 'repoSiteName' );

		$message = new Message( $repoSiteName );

		if ( $message->exists() ) {
			$lang = $context->getLanguage();
			$ret = $message->inLanguage( $lang )->parse();
		} else {
			$ret = $repoSiteName;
		}

		return Xml::encodeJsCall(
			'mw.jqueryMsg.setParserDefaults',
			[ [ 'WBREPONAME' => $ret ] ],
			ResourceLoader::inDebugMode()
		);
	}

	public function getDependencies() {
		return [ 'mediawiki.jqueryMsg' ];
	}

}
