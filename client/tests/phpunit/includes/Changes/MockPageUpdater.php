<?php

namespace Wikibase\Client\Tests\Changes;

use Title;
use Wikibase\Client\Changes\PageUpdater;
use Wikibase\EntityChange;

/**
 * Mock version of the service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used for testing ChangeHandler.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class MockPageUpdater implements PageUpdater {

	private $updates = array(
		'purgeParserCache' => [],
		'purgeWebCache' => [],
		'scheduleRefreshLinks' => [],
		'injectRCRecord' => [],
	);

	/**
	 * @param Title[] $titles
	 */
	public function purgeParserCache( array $titles ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['purgeParserCache'][ $key ] = $title;
		}
	}

	/**
	 * @param Title[] $titles
	 */
	public function purgeWebCache( array $titles ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['purgeWebCache'][ $key ] = $title;
		}
	}

	/**
	 * @param Title[] $titles
	 */
	public function scheduleRefreshLinks( array $titles ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['scheduleRefreshLinks'][ $key ] = $title;
		}
	}

	/**
	 * @param Title[] $titles
	 * @param EntityChange $change
	 */
	public function injectRCRecords( array $titles, EntityChange $change ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['injectRCRecord'][ $key ] = $change;
		}
	}

	public function getUpdates() {
		return $this->updates;
	}

}
