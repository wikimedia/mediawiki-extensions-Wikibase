<?php

namespace Wikibase\Client\Hooks;

use Content;
use ParserOutput;
use StubUserLang;
use Title;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\WikibaseClient;
use Wikibase\LangLinkHandler;
use Wikibase\NamespaceChecker;

/**
 * @since 0.5.
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class ParserOutputUpdateHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler;

	/**
	 * @var ClientParserOutputDataUpdater
	 */
	private $parserOutputDataUpdater;

	public static function newFromGlobalState() {
		global $wgLang;
		StubUserLang::unstub( $wgLang );

		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$settings = $wikibaseClient->getSettings();

		return new ParserOutputUpdateHookHandlers(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLangLinkHandler(),
			$wikibaseClient->getParserOutputDataUpdater(),
			$settings->getSetting( 'alwaysSort' )
		);
	}

	/**
	 * Static handler for the ContentAlterParserOutput hook.
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public static function onContentAlterParserOutput( Content $content, Title $title, ParserOutput $parserOutput ) {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$handler = self::newFromGlobalState();
		$handler->doContentAlterParserOutput( $title, $parserOutput );
	}

	/**
	 * @param NamespaceChecker $namespaceChecker
	 * @param LangLinkHandler $langLinkHandler
	 * @param ClientParserOutputDataUpdater $parserOutputDataUpdater
	 */
	public function __construct(
		NamespaceChecker $namespaceChecker,
		LangLinkHandler $langLinkHandler,
		ClientParserOutputDataUpdater $parserOutputDataUpdater
	) {

		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandler = $langLinkHandler;
		$this->parserOutputDataUpdater = $parserOutputDataUpdater;
	}

	/**
	 * Hook runs after internal parsing
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 *
	 * @return bool
	 */
	public function doContentAlterParserOutput( Title $title, ParserOutput $parserOutput ) {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return true;
		}

		$useRepoLinks = $this->langLinkHandler->useRepoLinks( $title, $parserOutput );

		if ( $useRepoLinks ) {
			// add links
			$this->langLinkHandler->addLinksFromRepository( $title, $parserOutput );
		}

		$this->parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );

		return true;
	}

}
