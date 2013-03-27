<?php

namespace Wikibase;

/**
 * Dating service for Terms.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface TermCombinationMatchFinder {

	/**
	 * Takes an array in which each element in array of of Term.
	 * These terms can be incomplete so the search is not restrained on some fields.
	 *
	 * Looks for terms of a single entity that has a matching term for each element in one of the array of Term.
	 * If a match is found, the terms for that entity are returned complete with entity id and entity type info.
	 * The result is thus either an empty array when no match is found or an array with Term elements of size
	 * equal to the provided array of Term that matched.
	 *
	 * $termType and $entityType can be provided as default constraints for terms not having these fields set.
	 *
	 * $excludeId and $excludeType can be used to exclude any terms for the entity that matches this info.
	 *
	 * @since 0.4
	 *
	 * @param array $terms
	 * @param string|null $termType
	 * @param string|null $entityType
	 * @param EntityId|null $excludeId
	 *
	 * @return array
	 */
	public function getMatchingTermCombination( array $terms, $termType = null, $entityType = null, EntityId $excludeId = null );

}
