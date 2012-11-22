<?php

namespace Wikibase;

/**
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
 * @since 0.2
 *
 * @file
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ClientChangeHandler {

	/**
	 * @since 0.3
	 *
	 * @return ClientChangeHandler
	 */
	public static function singleton() {
		static $instance = false;

		if ( $instance === false ) {
			$instance = new static();
		}

		return $instance;
	}

	/**
	 * @since 0.3
	 *
	 * @param Change $change
	 *
	 * @return bool
	 */
	public function changeNeedsRendering( Change $change ) {
		if ( $change instanceof ItemChange ) {
			if ( !$change->getSiteLinkDiff()->isEmpty() ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @since 0.3
	 *
	 * @param Change $change
	 *
	 * @return string|null
	 */
	public function siteLinkComment( $change ) {
		$comment = null;
		if ( !$change->getSiteLinkDiff()->isEmpty() ) {
			$siteLinkDiff = $change->getSiteLinkDiff();
			$changeKey = key( $siteLinkDiff );
			$diffOp = $siteLinkDiff[$changeKey];

			$action = 'change';
			if ( $diffOp instanceof \Diff\DiffOpAdd ) {
				$action = 'add';
			} else if ( $diffOp instanceof \Diff\DiffOpRemove ) {
				$action = 'remove';
			}

			$comment = "wbc-comment-sitelink-$action~" . key( $siteLinkDiff );
		}

		return $comment;
	}
}
