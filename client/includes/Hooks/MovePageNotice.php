<?php

namespace Wikibase\Client\Hooks;

use Html;
use MovePageForm;
use Title;
use Wikibase\Client\RepoLinker;
use Wikibase\Client\WikibaseClient;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * Gets a notice about the Wikibase Item belonging to the current page
 * after a move (in case there's one).
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class MovePageNotice {

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
	public function __construct( SiteLinkLookup $siteLinkLookup, $siteId, RepoLinker $repoLinker ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
		$this->repoLinker = $repoLinker;
	}

	private static function newFromGlobalState() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();
		$siteLinkLookup = $wikibaseClient->getStore()->getSiteLinkLookup();
		$repoLinker = $wikibaseClient->newRepoLinker();

		return new self(
			$siteLinkLookup,
			$wikibaseClient->getSettings()->getSetting( 'siteGlobalID' ),
			$repoLinker
		);
	}

	/**
	 * Hook for injecting a message on [[Special:MovePage]]
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/SpecialMovepageAfterMove
	 *
	 * @param MovePageForm $movePage
	 * @param Title &$oldTitle
	 * @param Title &$newTitle
	 *
	 * @return bool
	 */
	public static function onSpecialMovepageAfterMove( MovePageForm $movePage, Title &$oldTitle,
		Title &$newTitle ) {
		$self = self::newFromGlobalState();

		$self->doSpecialMovepageAfterMove( $movePage, $oldTitle, $newTitle );

		return true;
	}

	/**
	 * @param MovePageForm $movePage
	 * @param Title &$oldTitle
	 * @param Title &$newTitle
	 *
	 * @return bool
	 */
	public function doSpecialMovepageAfterMove(
		MovePageForm $movePage,
		Title &$oldTitle,
		Title &$newTitle
	) {
		$out = $movePage->getOutput();
		$out->addModules( 'wikibase.client.page-move' );
		$out->addHTML( $this->getPageMoveNoticeHtml( $oldTitle, $newTitle ) );
	}

	/**
	 * Create a repo link directly to the item.
	 * We can't use Special:ItemByTitle here as the item might have already been updated.
	 *
	 * @param Title $title
	 *
	 * @return string|null
	 */
	private function getItemUrl( Title $title ) {
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
	 *
	 * @return string|null
	 */
	private function getPageMoveNoticeHtml( Title $oldTitle, Title $newTitle ) {
		$itemLink = $this->getItemUrl( $oldTitle );

		if ( !$itemLink ) {
			return null;
		}

		$msg = $this->getPageMoveMessage( $newTitle );

		$html = Html::rawElement(
			'div',
			[
				'id' => 'wbc-after-page-move',
				'class' => 'plainlinks'
			],
			wfMessage( $msg, $itemLink )->parse()
		);

		return $html;
	}

	private function getPageMoveMessage( Title $newTitle ) {
		if ( isset( $newTitle->wikibasePushedMoveToRepo ) ) {
			// We're going to update the item using the repo job queue \o/
			return 'wikibase-after-page-move-queued';
		}

		// The user has to update the item per hand for some reason
		return 'wikibase-after-page-move';
	}

}
