<?php

namespace Wikibase\Client\Hooks;

use IContextSource;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\NamespaceChecker;

/**
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandler {

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker;

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	public function __construct( NamespaceChecker $namespaceChecker, RepoLinker $repoLinker,
		SiteLinkLookup $siteLinkLookup, $siteId ) {
		$this->namespaceChecker = $namespaceChecker;
		$this->repoLinker = $repoLinker;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
	}

	/**
	 * @param IContextSource $context
	 * @param array $pageInfo
	 *
	 * @return array
	 */
	public function handle( IContextSource $context, array $pageInfo ) {
		// Check if wikibase namespace is enabled
		$title = $context->getTitle();

		if ( $this->namespaceChecker->isWikibaseEnabled( $title->getNamespace() ) && $title->exists() ) {
			$pageInfo['header-basic'][] = $this->getPageInfoRow( $context, $title );
		}

		return $pageInfo;
	}

	/**
	 * @param IContextSource $context
	 * @param Title $title
	 *
	 * @return string[]
	 */
	private function getPageInfoRow( IContextSource $context, Title $title ) {
		$entityId = $this->siteLinkLookup->getItemIdForLink(
			$this->siteId,
			$title->getPrefixedText()
		);

		$row = $entityId ? $this->getItemPageInfo( $context, $entityId )
			: $this->getUnconnectedItemPageInfo( $context );

		return $row;
	}

	/**
	 * Creating a Repo link with Item ID as anchor text
	 *
	 * @param IContextSource $context
	 * @param ItemId $itemId
	 *
	 * @return string[]
	 */
	private function getItemPageInfo( IContextSource $context, ItemId $itemId ) {
		$itemLink = $this->repoLinker->buildEntityLink(
			$itemId,
			array( 'external' )
		);

		return array(
			$context->msg( 'wikibase-pageinfo-entity-id' ),
			$itemLink
		);
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return string[]
	 */
	private function getUnconnectedItemPageInfo( IContextSource $context ) {
		return array(
			$context->msg( 'wikibase-pageinfo-entity-id' ),
			$context->msg( 'wikibase-pageinfo-entity-id-none' )
		);
	}

}
