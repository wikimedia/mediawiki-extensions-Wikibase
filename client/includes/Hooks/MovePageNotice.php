<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Hooks;

use Html;
use MediaWiki\Hook\SpecialMovepageAfterMoveHook;
use MovePageForm;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\Store\ClientStore;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Gets a notice about the Wikibase Item belonging to the current page
 * after a move (in case there's one).
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class MovePageNotice implements SpecialMovepageAfterMoveHook {

	private bool $isMobileView;

	private SiteLinkLookup $siteLinkLookup;

	private string $siteId;

	private RepoLinker $repoLinker;

	/**
	 * @param bool $isMobileView
	 * @param SiteLinkLookup $siteLinkLookup
	 * @param string $siteId Global id of the client wiki
	 * @param RepoLinker $repoLinker
	 */
	public function __construct(
		bool $isMobileView,
		SiteLinkLookup $siteLinkLookup,
		string $siteId,
		RepoLinker $repoLinker
	) {
		$this->isMobileView = $isMobileView;
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
		$this->repoLinker = $repoLinker;
	}

	public static function factory(
		bool $isMobileView,
		RepoLinker $repoLinker,
		SettingsArray $clientSettings,
		ClientStore $store
	): self {
		return new self(
			$isMobileView,
			$store->getSiteLinkLookup(),
			$clientSettings->getSetting( 'siteGlobalID' ),
			$repoLinker
		);
	}

	/**
	 * Hook for injecting a message on [[Special:MovePage]]
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialMovepageAfterMove
	 *
	 * @param MovePageForm $movePage
	 * @param Title $oldTitle
	 * @param Title $newTitle
	 */
	public function onSpecialMovepageAfterMove( $movePage, $oldTitle, $newTitle ): void {
		$out = $movePage->getOutput();
		// T324991
		if ( !$this->isMobileView ) {
			$out->addModules( 'wikibase.client.miscStyles' );
		}

		$pageMoveNoticeHtml = $this->getPageMoveNoticeHtml( $oldTitle, $newTitle );
		if ( $pageMoveNoticeHtml ) {
			$out->addHTML( $pageMoveNoticeHtml );
		}
	}

	/**
	 * Create a repo link directly to the item.
	 * We can't use Special:ItemByTitle here as the item might have already been updated.
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
	 * @param Title $oldTitle Title of the page before the move
	 * @param Title $newTitle Title of the page after the move
	 */
	private function getPageMoveNoticeHtml( Title $oldTitle, Title $newTitle ): ?string {
		$itemLink = $this->getItemUrl( $oldTitle );

		if ( !$itemLink ) {
			return null;
		}

		$html = Html::rawElement(
			'div',
			[
				'id' => 'wbc-after-page-move',
				'class' => 'plainlinks',
			],
			wfMessage( 'wikibase-after-page-move', $itemLink )->parse()
		);

		return $html;
	}

}
