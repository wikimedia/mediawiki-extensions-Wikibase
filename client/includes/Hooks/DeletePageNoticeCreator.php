<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Html;
use MediaWiki\Page\Hook\ArticleDeleteAfterSuccessHook;
use OutputPage;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Creates a notice about the Wikibase Item belonging to the current page
 * after a delete (in case there's one).
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class DeletePageNoticeCreator implements ArticleDeleteAfterSuccessHook {

	/**
	 * @var SiteLinkLookup
	 */
	private $siteLinkLookup;

	/**
	 * @var string
	 */
	private $siteId;

	/**
	 * @var RepoLinker
	 */
	private $repoLinker;

	/**
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId Global id of the client wiki
	 * @param RepoLinker $repoLinker
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup, string $siteId, RepoLinker $repoLinker ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
		$this->repoLinker = $repoLinker;
	}

	public static function factory(
		RepoLinker $repoLinker,
		SettingsArray $clientSettings,
		ClientStore $store
	): self {
		return new self(
			$store->getSiteLinkLookup(),
			$clientSettings->getSetting( 'siteGlobalID' ),
			$repoLinker
		);
	}

	/**
	 * @param Title $title
	 * @param OutputPage $outputPage
	 */
	public function onArticleDeleteAfterSuccess( $title, $outputPage ): void {
		$outputPage->addHTML( $this->getPageDeleteNoticeHtml( $title ) );
	}

	/**
	 * Create a repo link directly to the item.
	 * We can't use Special:ItemByTitle here as the item might have already been updated.
	 *
	 * @param Title $title
	 *
	 * @return string|null
	 */
	private function getItemUrl( Title $title ): ?string {
		$entityId = $this->siteLinkLookup->getItemIdForLink(
			$this->siteId,
			$title->getPrefixedText()
		);

		if ( !$entityId ) {
			return null;
		}

		return $this->repoLinker->getEntityUrl( $entityId );
	}

	/**
	 * @param Title $title
	 *
	 * @return string|null
	 */
	public function getPageDeleteNoticeHtml( Title $title ): ?string {
		$itemLink = $this->getItemUrl( $title );

		if ( !$itemLink ) {
			return null;
		}

		$msg = $this->getMessage( $title );

		$html = Html::rawElement(
			'div',
			[
				'class' => 'plainlinks',
			],
			wfMessage( $msg, $itemLink )->parse()
		);

		return $html;
	}

	private function getMessage( Title $title ): string {
		if ( isset( $title->wikibasePushedDeleteToRepo ) ) {
			// We're going to update the item using the repo job queue \o/
			return 'wikibase-after-page-delete-queued';
		}

		// The user has to update the item per hand for some reason
		return 'wikibase-after-page-delete';
	}

}
