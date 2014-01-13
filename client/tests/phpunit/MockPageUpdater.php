<?php

namespace Wikibase\Test;

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
 *
 */
class MockPageUpdater implements \Wikibase\PageUpdater {

	protected $updates = array(
		'purgeParserCache' => array(),
		'purgeWebCache' => array(),
		'scheduleRefreshLinks' => array(),
		'injectRCRecord' => array(),
	);

	public function purgeParserCache( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['purgeParserCache'][ $key ] = $title;
		}
	}

	public function purgeWebCache( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['purgeWebCache'][ $key ] = $title;
		}
	}

	public function scheduleRefreshLinks( array $titles ) {
		/* @var \Title $title */
		foreach ( $titles as $title ) {
			$key = $title->getPrefixedDBkey();
			$this->updates['scheduleRefreshLinks'][ $key ] = $title;
		}
	}

	public function injectRCRecord( \Title $title, array $attribs ) {
		$key = $title->getPrefixedDBkey();
		$this->updates['injectRCRecord'][ $key ] = $attribs;

		return true;
	}

	public function getUpdates() {
		return $this->updates;
	}

}