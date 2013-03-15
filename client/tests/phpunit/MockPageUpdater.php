<?php

namespace Wikibase\Test;

/**
 * Mock version of the service object for triggering different kinds of page updates
 * and generally notifying the local wiki of external changes.
 *
 * Used for testing ChangeHandler.
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