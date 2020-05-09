<?php

namespace Wikibase\Client\Hooks;

use Content;
use ParserOutput;
use StubUserLang;
use Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\Usage\EntityUsageFactory;
use Wikibase\Client\Usage\ParserOutputUsageAccumulator;
use Wikibase\Client\WikibaseClient;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputUpdateHookHandlers {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var LangLinkHandlerFactory
	 */
	private $langLinkHandlerFactory;

	/**
	 * @var ClientParserOutputDataUpdater
	 */
	private $parserOutputDataUpdater;

	/**
	 * @var EntityUsageFactory
	 */
	private $entityUsageFactory;

	/**
	 * @return self
	 */
	public static function newFromGlobalState() {
		global $wgLang;

		StubUserLang::unstub( $wgLang );
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		return new self(
			$wikibaseClient->getNamespaceChecker(),
			$wikibaseClient->getLangLinkHandlerFactory(),
			$wikibaseClient->getParserOutputDataUpdater(),
			new EntityUsageFactory( $wikibaseClient->getEntityIdParser() )
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
		LangLinkHandlerFactory $langLinkHandlerFactory,
		ClientParserOutputDataUpdater $parserOutputDataUpdater,
		EntityUsageFactory $entityUsageFactory
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandlerFactory = $langLinkHandlerFactory;
		$this->parserOutputDataUpdater = $parserOutputDataUpdater;
		$this->entityUsageFactory = $entityUsageFactory;
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

		$usageAccumulator = new ParserOutputUsageAccumulator( $parserOutput, $this->entityUsageFactory );
		$langLinkHandler = $this->langLinkHandlerFactory->getLangLinkHandler( $usageAccumulator );
		$useRepoLinks = $langLinkHandler->useRepoLinks( $title, $parserOutput );

		if ( $useRepoLinks ) {
			// add links
			$langLinkHandler->addLinksFromRepository( $title, $parserOutput );
		}

		$this->parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateTrackingCategories( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutput );
		$this->parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutput );

		return true;
	}

}
