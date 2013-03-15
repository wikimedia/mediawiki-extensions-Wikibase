<?php

namespace Wikibase;
use Diff\Diff;

/**
 * Represents a diff between two Wikibase\Item instances.
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
class ItemDiff extends EntityDiff {

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getSiteLinkDiff() {
		return isset( $this['links'] ) ? $this['links'] : new Diff( array(), true );
	}

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getClaimDiff() {
		return isset( $this['claim'] ) ? $this['claim'] : new Diff( array(), true );
	}

	/**
	 * Returns a Diff object with the sitelink differences.
	 *
	 * @since 0.1
	 *
	 * @return Diff
	 */
	public function getLabelDiff() {
		return isset( $this['label'] ) ? $this['label'] : new Diff( array(), true );
	}

	/**
	 * @see EntityDiff::isEmpty
	 *
	 * @since 0.1
	 *
	 * @return boolean
	 */
	public function isEmpty() {
		return parent::isEmpty()
			&& $this->getSiteLinkDiff()->isEmpty();
	}

	/**
	 * @see DiffOp::getType();
	 *
	 * @return string 'diff/' . Item::ENTITY_TYPE
	 */
	public function getType() {
		return 'diff/' . Item::ENTITY_TYPE;
	}
}