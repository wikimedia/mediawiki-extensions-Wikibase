<?php

namespace Wikibase;

use FormatJson;
use MediaWikiSite;
use Message;
use ResourceLoader;
use ResourceLoaderContext;
use ResourceLoaderModule;
use Wikibase\Client\WikibaseClient;
use Xml;
use XmlJsCode;

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
		$lang = $context->getLanguage();
		$message->inLanguage( $lang );

		if ( $message->exists() ) {
			$ret = $message->parse();
		} else {
			$ret = $repoSiteName;
		}

		return 'mw.jqueryMsg.setParserDefaults( { magic: $.extend( ' .
			'mw.jqueryMsg.getParserDefaults().magic, ' .
			FormatJson::encode( [ 'WBREPONAME' => $ret ], ResourceLoader::inDebugMode() ) .
			' ) } );';
	}

	public function getDependencies() {
		return [ 'mediawiki.jqueryMsg' ];
	}

}
