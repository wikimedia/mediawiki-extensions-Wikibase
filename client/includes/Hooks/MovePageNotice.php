<?php

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

	public static function factory(
		RepoLinker $repoLinker,
		SettingsArray $clientSettings,
		ClientStore $store
	) {
		return new self(
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
	public function onSpecialMovepageAfterMove( $movePage, $oldTitle, $newTitle ) {
		$out = $movePage->getOutput();
		$out->addModules( 'wikibase.client.miscStyles' );
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
