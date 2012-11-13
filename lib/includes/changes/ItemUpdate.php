<?php

namespace Wikibase;

/**
 * Class representing an update to an entity.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemUpdate extends EntityUpdate {

	/**
	 * @since 0.3
	 *
	 * @return MapDiff|bool
	 * @throws \MWException
	 */
	public function getSiteLinkDiff() {
		$diff = $this->getDiff();

		if ( !$diff instanceof ItemDiff ) {
			throw new \MWException( 'Cannot get sitelink diff for ' . get_class( $diff ) . '.' );
		}

		return $this->getDiff()->getSiteLinkDiff();
	}

	public function getSiteLinkChangeOperations() {
		return $this->getDiff()->getSiteLinkDiff()->getTypeOperations( 'change' );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $globalId
	 *
	 * @return \Title
	 */
	public function getOldTitle( $globalId ) {
		$diffOp = $this->getSiteLinkChangeOperations();
		return \Title::newFromText( $diffOp[$globalId]->getOldValue() );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $globalId
	 *
	 * @return \Title
	 */
	public function getNewTitle( $globalId ) {
		$diffOp = $this->getSiteLinkChangeOperations();
		return \Title::newFromText( $diffOp[$globalId]->getNewValue() );
	}
}
