<?php

namespace Wikibase\Client\Tests\Integration\Changes;

use Title;
use Wikibase\Client\Changes\PageUpdater;
use Wikibase\Lib\Changes\EntityChange;

/**
 * Mock version of the service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used for testing ChangeHandler.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class MockPageUpdater implements PageUpdater {

	/**
	 * @var array[] Collections of affected objects as provided to the individual methods
	 */
	private $updates = [
		'purgeWebCache' => [],
		'scheduleRefreshLinks' => [],
		'injectRCRecord' => [],
	];

	/**
	 * @var array[] Collections of root job parameters as provided to the individual methods
	 */
	private $rootJobParams = [
		'purgeWebCache' => [],
		'scheduleRefreshLinks' => [],
		'injectRCRecord' => [],
	];

	/**
	 * @param Title[] $titles
	 * @param array $rootJobParams
	 * @param string $causeAction
	 * @param string $causeAgent
	 */
	public function purgeWebCache(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['purgeWebCache'][ $key ] = $title;
		}

		$this->rootJobParams['purgeWebCache'] += $rootJobParams;
	}

	/**
	 * @param Title[] $titles
	 * @param array $rootJobParams
	 * @param string $causeAction
	 * @param string $causeAgent
	 */
	public function scheduleRefreshLinks(
		array $titles,
		array $rootJobParams,
		$causeAction,
		$causeAgent
	) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['scheduleRefreshLinks'][ $key ] = $title;
		}

		$this->rootJobParams['scheduleRefreshLinks'] += $rootJobParams;
	}

	/**
	 * @param Title[] $titles
	 * @param EntityChange $change
	 * @param array $rootJobParams
	 */
	public function injectRCRecords( array $titles, EntityChange $change, array $rootJobParams = [] ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['injectRCRecord'][ $key ] = $change;
		}

		$this->rootJobParams['injectRCRecord'] += $rootJobParams;
	}

	/**
	 * @return array[]
	 */
	public function getUpdates() {
		return $this->updates;
	}

	/**
	 * @return array[]
	 */
	public function getRootJobParams() {
		return $this->rootJobParams;
	}

}
