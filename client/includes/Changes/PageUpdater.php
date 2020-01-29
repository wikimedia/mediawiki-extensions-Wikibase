<?php

namespace Wikibase\Client\Changes;

use Title;
use Wikibase\Lib\Changes\EntityChange;

/**
 * Service interface for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used by ChangeHandler as an interface to the local wiki.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
interface PageUpdater {

	/**
	 * Invalidates external web cached of the given pages.
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 * @param array $rootJobParams any relevant root job parameters to be inherited by new jobs.
	 * @param string $causeAction Triggering action
	 * @param string $causeAgent Triggering agent
	 */
	public function purgeWebCache(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	);

	/**
	 * Schedules RefreshLinks jobs for the given titles
	 *
	 * @param Title[] $titles The Titles of the pages to update
	 * @param array $rootJobParams any relevant root job parameters to be inherited by new jobs.
	 * @param string $causeAction Triggering action
	 * @param string $causeAgent Triggering agent
	 */
	public function scheduleRefreshLinks(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	);

	/**
	 * Injects an RC entry into the recentchanges, using the given title and attribs
	 *
	 * @param Title[] $titles
	 * @param EntityChange $change
	 * @param array $rootJobParams any relevant root job parameters to be inherited by new jobs.
	 */
	public function injectRCRecords( array $titles, EntityChange $change, array $rootJobParams = [] );

}
