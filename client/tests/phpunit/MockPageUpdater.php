<?php

namespace Wikibase\Test;

use Title;
use Wikibase\PageUpdater;

/**
 * Mock version of the service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used for testing ChangeHandler.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockPageUpdater implements PageUpdater {

	protected $updates = array(
		'purgeParserCache' => array(),
		'purgeWebCache' => array(),
		'scheduleRefreshLinks' => array(),
		'injectRCRecord' => array(),
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
	 * @param array $attribs
	 */
	public function injectRCRecords( array $titles, array $attribs ) {
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['injectRCRecord'][ $key ] = $attribs;
		}
	}

	public function getUpdates() {
		return $this->updates;
	}

}
