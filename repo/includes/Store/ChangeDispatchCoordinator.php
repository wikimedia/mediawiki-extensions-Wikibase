<?php

namespace Wikibase\Store;

use Exception;
use MWException;

/**
 * Service interface for a inter-process locking service intended to coordinate
 * message dispatching between multiple processes.
 *
 * The purpose of a ChangeDispatchCoordinator is to determine which client wiki to dispatch
 * to next in a fair manner, and to prevent multiple processes to try and dispatch to the
 * same wiki at once.
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
interface ChangeDispatchCoordinator {

	/**
	 * Selects a client wiki and locks it. If no suitable client wiki can be found,
	 * this method returns null.
	 *
	 * Note: this implementation will try a wiki from the list returned by getCandidateClients()
	 * at random. If all have been tried and failed, it returns null.
	 *
	 * @return array|null An associative array containing the state of the selected client wiki
	 *               (or null, if no target could be locked). Fields are:
	 *
	 * * chd_site:     the client wiki's global site ID
	 * * chd_db:       the client wiki's logical database name
	 * * chd_seen:     the last change ID processed for that client wiki
	 * * chd_touched:  timestamp giving the last time that client wiki was updated
	 * * chd_lock:     the name of a global lock currently active for that client wiki
	 *
	 * @throws MWException if no available client wiki could be found.
	 *
	 * @see releaseWiki()
	 */
	public function selectClient();

	/**
	 * Initializes the dispatch table by injecting dummy records for all target wikis
	 * that are in the configuration but not yet in the dispatch table.
	 *
	 * @param string[] $clientWikiDBs Associative array mapping client wiki IDs to
	 * client wiki (logical) database names.
	 */
	public function initState( array $clientWikiDBs );

	/**
	 * Attempt to lock the given target wiki. If it can't be locked because
	 * another dispatch process is working on it, this method returns false.
	 *
	 * @param string $siteID The ID of the client wiki to lock.
	 *
	 * @throws Exception
	 * @return array|false An associative array containing the state of the selected client wiki
	 *               (see selectClient()) or false if the client wiki could not be locked.
	 *
	 * @see selectClient()
	 */
	public function lockClient( $siteID );

	/**
	 * Updates the given client wiki's entry in the dispatch table and
	 * releases the global lock on that wiki.
	 *
	 * The $state parameter represents the client wiki's state after the update pass.
	 * Its structure must be the one returned by selectClient(), with the chd_seen and
	 * field updated to reflect any dispatch activity.
	 *
	 * @param array $state Associative array representing the client wiki's state after the
	 *                      update pass.
	 *
	 * @throws Exception
	 * @see selectWiki()
	 */
	public function releaseClient( array $state );

}
