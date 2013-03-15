<?php

namespace Wikibase;

/**
 * Service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 *
 */
class ChangeHandlerActions {

	/**
	 * Invalidates local cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function purgeParserCache( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": purging page " . $title->getText() );
			$title->invalidateCache();
		}
	}

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function purgeWebCache( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": purging page " . $title->getText() );
			$title->purgeSquid();
		}
	}

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @since    0.4
	 *
	 * @param \Title[] $titles The Titles of the pages to update
	 */
	public function scheduleRefreshLinks( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			wfDebugLog( __CLASS__, __FUNCTION__ . ": scheduling page " . $title->getText() );

			//XXX: use \RefreshLinksJob2 ?!
			$job = new \RefreshLinksJob( $title );

			\JobQueueGroup::singleton()->push( $job );
			\JobQueueGroup::singleton()->deduplicateRootJob( $job );
		}
	}

	/**
	 * Injects an RC entry into the recentchanges, using the the given title and attribs
	 *
	 * @param \Title $title
	 * @param array $attribs
	 *
	 * @return bool
	 */
	public function injectRCRecord( \Title $title, array $attribs ) {
		wfProfileIn( __METHOD__ );

		if ( !$title->exists() ) {
			wfProfileOut( __METHOD__ );
			return false;
		}

		//FIXME: The same change may be reported to several target pages;
		//       The comment we generate should be adapted to the role that page
		//       plays in the change, e.g. when a sitelink changes from one page to another,
		//       the link was effectively removed from one and added to the other page.
		$rc = ExternalRecentChange::newFromAttribs( $attribs, $title );

		// @todo batch these
		wfDebugLog( __CLASS__, __FUNCTION__ . ": saving RC entry for " . $title->getFullText() );
		$rc->save();

		wfProfileOut( __METHOD__ );
		return true;
	}
}