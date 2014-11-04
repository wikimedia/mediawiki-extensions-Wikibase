<?php

namespace Wikibase\Client\Changes;

use Title;

/**
 * Service interface for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 *
 */
interface PageUpdater {

	/**
	 * Invalidates local cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function purgeParserCache( array $titles );

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @since    0.4
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function purgeWebCache( array $titles );

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @since    0.4
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function scheduleRefreshLinks( array $titles );

	/**
	 * Injects an RC entry into the recentchanges, using the the given title and attribs
	 *
	 * @since 0.5
	 *
	 * @param Title[] $titles
	 * @param array $attribs
	 */
	public function injectRCRecords( array $titles, array $attribs );

}
