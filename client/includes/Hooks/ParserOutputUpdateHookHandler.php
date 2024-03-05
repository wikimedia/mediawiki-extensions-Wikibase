<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Content;
use MediaWiki\Content\Hook\ContentAlterParserOutputHook;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use Wikibase\Client\NamespaceChecker;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\ParserOutput\ScopedParserOutputProvider;
use Wikibase\Client\Usage\UsageAccumulatorFactory;

/**
 * @license GPL-2.0-or-later
 */
class ParserOutputUpdateHookHandler implements ContentAlterParserOutputHook {

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
	 * @var UsageAccumulatorFactory
	 */
	private $usageAccumulatorFactory;

	public function __construct(
		LangLinkHandlerFactory $langLinkHandlerFactory,
		NamespaceChecker $namespaceChecker,
		ClientParserOutputDataUpdater $parserOutputDataUpdater,
		UsageAccumulatorFactory $usageAccumulatorFactory
	) {
		$this->namespaceChecker = $namespaceChecker;
		$this->langLinkHandlerFactory = $langLinkHandlerFactory;
		$this->parserOutputDataUpdater = $parserOutputDataUpdater;
		$this->usageAccumulatorFactory = $usageAccumulatorFactory;
	}

	/**
	 * Handler for the ContentAlterParserOutput hook, which runs after internal parsing.
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/ContentAlterParserOutput
	 *
	 * @param Content $content
	 * @param Title $title
	 * @param ParserOutput $parserOutput
	 */
	public function onContentAlterParserOutput( $content, $title, $parserOutput ): void {
		// this hook tries to access repo SiteLinkTable
		// it interferes with any test that parses something, like a page or a message
		if ( defined( 'MW_PHPUNIT_TEST' ) ) {
			return;
		}

		$this->doContentAlterParserOutput( $content, $title, $parserOutput );
	}

	/**
	 * @internal only public for testing (to bypass the test skip in onContentAlterParserOutput)
	 */
	public function doContentAlterParserOutput( Content $content, Title $title, ParserOutput $parserOutput ): void {
		if ( !$this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) ) {
			// shorten out
			return;
		}

		$parserOutputProvider = new ScopedParserOutputProvider( $parserOutput );
		$usageAccumulator = $this->usageAccumulatorFactory->newFromParserOutputProvider( $parserOutputProvider );
		$langLinkHandler = $this->langLinkHandlerFactory->getLangLinkHandler( $usageAccumulator );
		$useRepoLinks = $langLinkHandler->useRepoLinks( $title, $parserOutput );

		if ( $useRepoLinks ) {
			// add links
			$langLinkHandler->addLinksFromRepository( $title, $parserOutput );
		}

		$this->parserOutputDataUpdater->updateItemIdProperty( $title, $parserOutputProvider );
		$this->parserOutputDataUpdater->updateTrackingCategories( $title, $parserOutputProvider );
		$this->parserOutputDataUpdater->updateOtherProjectsLinksData( $title, $parserOutputProvider );
		$this->parserOutputDataUpdater->updateUnconnectedPageProperty( $content, $title, $parserOutputProvider );
		$this->parserOutputDataUpdater->updateBadgesProperty( $title, $parserOutputProvider );
		$parserOutputProvider->close();
	}

}
