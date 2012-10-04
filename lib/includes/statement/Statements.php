<?php

namespace Wikibase;

/**
 * List of Statement objects.
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
 * @since 0.2
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Statements extends \Traversable, \Countable, \Serializable, Hashable {

	/**
	 * Adds the provided statement to the list.
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function addStatement( Statement $statement );

	/**
	 * Returns if the list contains a statement with the same hash as the provided statement.
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 *
	 * @return boolean
	 */
	public function hasStatement( Statement $statement );

	/**
	 * Removes the statement with the same hash as the provided reference if such a statement exists in the list.
	 *
	 * @since 0.2
	 *
	 * @param Statement $statement
	 */
	public function removeStatement( Statement $statement );

}
