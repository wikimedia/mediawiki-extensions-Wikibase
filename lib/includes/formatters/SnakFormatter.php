<?php

namespace Wikibase;

/**
 * Formatter for Snak
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
 */
class SnakFormatter {

	/**
	 * Format a Snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	public function format( Snak $snak ) {
		$snakType = $snak->getType();

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

			// FIXME! should use some value formatter
			if ( $dataValue instanceof EntityId ) {
				$rawValue = $this->getEntityLabel( $dataValue );
			} else {
				$rawValue = $dataValue->getValue();
			}

			return $rawValue;
		} else {
			return $snakType;
		}
	}

}
