<?php

namespace Wikibase;
use \Diff\DiffOpFactory;

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
 * @since 0.3
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ItemChange extends EntityChange {

	/**
	 * @since 0.3
	 *
	 * @return \Diff\MapDiff|bool
	 * @throws \MWException
	 */
	public function getSiteLinkDiff() {
		$diff = $this->getDiff();

		if ( is_array( $diff ) ) {
			// @todo: put in a nicer place
			$diffOpFactory = new DiffOpFactory();
			$diffOps = array();

			foreach( $diff['operations'] as $key => $opArray ) {
				$diffOps[$key] = $diffOpFactory->newFromArray( $opArray );
			}

			$itemDiff = new ItemDiff( $diffOps );
		} else if ( $diff instanceof ItemDiff ) {
			$itemDiff = $diff;
		} else {
			throw new \MWException( 'Cannot get sitelink diff for ' . get_class( $diff ) . '.' );
		}

		return $itemDiff->getSiteLinkDiff();
	}
}
