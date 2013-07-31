<?php

namespace Wikibase\Lib;

use \ValueFormatters\TimeIsoFormatter;

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
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class MwTimeIsoFormatter implements TimeIsoFormatter {

	/**
	 * MediaWiki language object.
	 * @var \Language
	 */
	private $language;

	public function __construct( $language ) {
		$this->language = $language;
	}

	/**
	 * @see TimeIsoFormatter::formatDate
	 */
	public function formatDate( $extendedIsoTimestamp, $precision ) {
		if(
			// TODO: Localize dates not featuring a positive 4-digit year.
			preg_match( '/^\+0*(\d{4})-/', $extendedIsoTimestamp, $matches )
			// TODO: Support precision above year
			&& $precision >= 9
		) {
			// Positive 4-digit year allows using Language object.
			$strippedTime = preg_replace( '/^(\+0*)(\d{4})/', '$2', $extendedIsoTimestamp );

			$timestamp = wfTimestamp( TS_MW, $strippedTime );
			$dateFormat = $this->language->getDateFormatString(
				'date',
				$this->language->getDefaultDateFormat()
			);

			// TODO: Implement more sophisticated replace algorithm since characters may be escaped
			//  or, even better, find a way to avoid having to do replacements.
			if( $precision < 11 ) {
				// Remove day placeholder:
				$dateFormat = preg_replace( '/((x\w{1})?(j|t)|d)/', '', $dateFormat );
			}

			if( $precision < 10 ) {
				// Remove month placeholder:
				$dateFormat = preg_replace( '/((x\w{1})?(F|n)|m)/', '', $dateFormat );
			}

			// TODO: Currently, the year will always be formatted with 4 digits. Years < 1000 will
			//  features leading zero(s) that would need to be stripped.
			return $this->language->sprintfDate( trim( $dateFormat ), $timestamp );
		} else {
			return $extendedIsoTimestamp;
		}
	}

}
