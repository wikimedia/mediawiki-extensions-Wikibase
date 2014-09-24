<?php

namespace Wikibase\Client\Hooks;

use Parser;
use ParserOutput;
use StripState;
use Title;
use Wikibase\Client\Store\UsageUpdater;
use Wikibase\Client\WikibaseClient;
use Wikibase\NamespaceChecker;
use Wikibase\Updates\DataUpdateAdapter;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;

/**
 * Hook handlers for triggering data updates.
 *
 * This class has a static interface for use with MediaWiki's hook mechanism; the static
 * handler functions will create a new instance of DataUpdateHookHandlers and then call the
 * corresponding member function on that.
 *
 * @since 0.5.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DataUpdateHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var UsageUpdater
	 */
	private $usageUpdater;

	public static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		$namespaceChecker = $wikibaseClient->getNamespaceChecker();
		$usageUpdater = new UsageUpdater(
			$settings->getSetting( 'siteGlobalID' ),
			$wikibaseClient->getStore()->getUsageTracker(),
			$wikibaseClient->getStore()->getUsageLookup(),
			$wikibaseClient->getStore()->getSubscriptionManager()
		);

		return new DataUpdateHookHandlers(
			$namespaceChecker,
			$usageUpdater
		);
	}

	/**
	 * Static handler for the ParserAfterParse hook.
	 *
	 * @param Parser &$parser
	 * @param string &$text
	 * @param StripState $stripState
	 *
	 * @return bool
	 */
	public static function onParserAfterParse( Parser &$parser, &$text, StripState $stripState ) {
		$handler = self::newFromGlobalState();
		return $handler->doParserAfterParse( $parser, $text, $stripState );
	}

	public function __construct(
		NamespaceChecker $namespaceChecker,
		UsageUpdater $usageUpdater
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->usageUpdater = $usageUpdater;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterParse
	 *
	 * @param Parser &$parser
	 * @param string &$text
	 * @param StripState $stripState
	 *
	 * @return bool
	 */
	public function doParserAfterParse( Parser &$parser, &$text, StripState $stripState ) {
		$title = $parser->getTitle();

		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		// only run this once, for the article content and not interface stuff
		//FIXME: this also runs for messages in EditPage::showEditTools! Ugh!
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			wfProfileOut( __METHOD__ );
			return true;
		}

		wfProfileIn( __METHOD__ );

		$this->registerDataUpdates( $title, $parser->getOutput() );

		wfProfileOut( __METHOD__ );
		return true;
	}

	private function registerDataUpdates( Title $title, ParserOutput $parserOutput ) {
		$usageAcc = new ParserOutputUsageAccumulator( $parserOutput );

		$update = new DataUpdateAdapter(
			array( $this->usageUpdater, 'updateUsageForPage' ),
			$title->getArticleId(),
			$usageAcc->getUsages()
		);

		$parserOutput->addSecondaryDataUpdate( $update );
	}

}
