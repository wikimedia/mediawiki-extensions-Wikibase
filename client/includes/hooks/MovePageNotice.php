<?php

namespace Wikibase\Client;
use Wikibase\SiteLinkLookup;
use Wikibase\RepoLinker;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Adds a notice about the Wikibase Item belonging to the current page
 * after a move (in case there's one).
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */

final class MovePageNotice {

	/**
	 * @var \SiteLinkLookup
	 */
	protected $siteLinkLookup;

	/**
	 * @var string
	 */
	protected $siteId;

	/**
	 * @var RepoLinker
	 */
	protected $repoLinker;

	/**
	 * @param Wikibase\SiteLinkLookup $siteLinkLookup
	 * @param string $siteId Global id of the client wiki
	 * @param Wikibase\RepoLinker $repoLinker
	 */
	public function __construct( SiteLinkLookup $siteLinkLookup, $siteId, RepoLinker $repoLinker ) {
		$this->siteLinkLookup = $siteLinkLookup;
		$this->siteId = $siteId;
		$this->repoLinker = $repoLinker;
	}

	/**
	 * Create a repo link directly to the item.
	 * We can't use Special:ItemByTitle here as the item might have already been updated.
	 *
	 * @param \Title $title
	 *
	 * @return string|null
	 */
	protected function getItemUrl( $title ) {
		$entityId = $this->siteLinkLookup->getEntityIdForSiteLink(
			new SimpleSiteLink(
				$this->siteId,
				$title->getFullText()
			)
		);

		if ( !$entityId ) {
			return null;
		}

		return $this->repoLinker->repoItemUrl( $entityId );
	}

	/**
	 * Append the appropriate content to the page
	 *
	 * @param \OutputPage $output
	 * @param \Title $oldTitle Title of the page before the move
	 * @param \Title $newTitle Title of the page after the move
	 */
	public function reportRepoUpdate( \OutputPage $out, \Title $oldTitle, \Title $newTitle ) {
		$itemLink = $this->getItemUrl( $oldTitle );

		if ( !$itemLink ) {
			return;
		}

		if ( isset( $newTitle->wikibasePushedMoveToRepo ) ) {
			// We're going to update the item using the repo job queue \o/
			$msg = 'wikibase-after-page-move-queued';
		} else {
			// The user has to update the item per hand for some reason
			$msg = 'wikibase-after-page-move';
		}

		$out->addModules( 'wikibase.client.page-move' );
		$out->addHTML(
			\Html::rawElement(
				'div',
				array( 'id' => 'wbc-after-page-move',
						'class' => 'plainlinks' ),
				wfMessage( $msg, $itemLink )->parse()
			)
		);
	}
}
