<?php

namespace Wikibase;

/**
 * Property lookup by label
 *
 * @todo use terms table to do lookups, add caching and tests
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
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 */
interface PropertyLookup {

	/**
	 * Returns these claims from the given entity that have a main Snak for the property
	 * identified by $propertyLabel in the language given by $langCode.
	 *
	 * @since    0.4
	 *
	 * @param Entity $entity
	 * @param string $propertyLabel
	 * @param string $langCode
	 *
	 * @return Claims
	 */
	public function getClaimsByPropertyLabel( Entity $entity, $propertyLabel, $langCode );

}
