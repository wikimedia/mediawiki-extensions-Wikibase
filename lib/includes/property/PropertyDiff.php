<?php

namespace Wikibase;
use Diff\MapDiff;

/**
 * Represents a diff between two WikibaseProperty instances.
 * Acts as a container for diffs between the various fields
 * of the propertys. Also contains methods to obtain these
 * diffs as Wikibase\Change objects.
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
 * Immutable.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Jens Ohlig
 */
class PropertyDiff extends EntityDiff implements \Immutable {

	/**
	 * Creates a new PropertyDiff representing the difference between $oldProperty and $newProperty
	 *
	 * @since 0.1
	 *
	 * @param Property $oldProperty
	 * @param Property $newProperty
	 * @return EntityDiff
	 */
	public static function newFromProperties( Property $oldProperty, Property $newProperty ) {
		return parent::newFromEntities( $oldProperty, $newProperty, array() );
	}


	/**
	 * @see EntityDiff::getView
	 *
	 * @since 0.1
	 *
	 * @return PropertyDiffView
	 */
	public function getView() {
		return new PropertyDiffView( array(), $this );
	}



}
