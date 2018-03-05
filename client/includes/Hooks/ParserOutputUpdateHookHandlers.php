<?php

namespace Wikibase\Client\Hooks;

use Content;
use ParserOutput;
use StubUserLang;
use Title;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\WikibaseClient;
use Wikibase\Client\LangLinkHandler;
use Wikibase\Client\NamespaceChecker;

/**
 * @license GPL-2.0-or-later
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

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang;

		StubUserLang::unstub( $wgLang );
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLangLinkHandler(),
			$wikibaseClient->getParserOutputDataUpdater()
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
		$this->parserOutputDataUpdater->updateTrackingCategories( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );

		return true;
	}

}
