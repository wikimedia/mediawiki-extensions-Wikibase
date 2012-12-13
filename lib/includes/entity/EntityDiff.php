<?php

namespace Wikibase;
use Diff\MapDiff;
use Diff\DiffOp;


/**
 * Represents a diff between two Wikibase\Entity instances.
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
class EntityDiff extends MapDiff {

	/**
	 * Returns a MapDiff object with the aliases differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getAliasesDiff() {
		return isset( $this['aliases'] ) ? $this['aliases'] : new MapDiff( array() );
	}

	/**
	 * Returns a MapDiff object with the labels differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getLabelsDiff() {
		return isset( $this['label'] ) ? $this['label'] : new MapDiff( array() );
	}

	/**
	 * Returns a MapDiff object with the descriptions differences.
	 *
	 * @since 0.1
	 *
	 * @return MapDiff
	 */
	public function getDescriptionsDiff() {
		return isset( $this['description'] ) ? $this['description'] : new MapDiff( array() );
	}

}
