<?php

namespace Wikibase;

/**
 * Interface for objects that represent a single Wikibase snak.
 * See https://meta.wikimedia.org/wiki/Wikidata/Data_model#Snaks
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
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface Snak extends \Serializable, Hashable, \Immutable {

	/**
	 * Returns the id of the snaks property.
	 *
	 * @since 0.2
	 *
	 * @return EntityId
	 */
	public function getPropertyId();

	/**
	 * Returns a string that can be used to identify the type of snak.
	 *
	 * @since 0.2
	 *
	 * @return string
	 */
	public function getType();

}