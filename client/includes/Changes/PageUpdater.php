<?php

namespace Wikibase\Client\Changes;

use Title;
use Wikibase\EntityChange;

/**
 * Service interface for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface PageUpdater {

	/**
	 * Invalidates local cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function purgeParserCache( array $titles );

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function purgeWebCache( array $titles );

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 */
	public function scheduleRefreshLinks( array $titles );

	/**
	 * Injects an RC entry into the recentchanges, using the the given title and attribs
	 *
	 * @param Title[] $titles
	 * @param EntityChange $change
	 */
	public function injectRCRecords( array $titles, EntityChange $change );

}
