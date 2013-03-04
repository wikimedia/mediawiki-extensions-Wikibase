<?php

namespace Wikibase\ParserFunction;

/**
 * Parser function for requesting description texts
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
 * @author Jeblad < jeblad@gmail.com >
 */
class DescriptionFunction extends EntityFunction {

	/**
	 * Parser function
	 *
	 * @since 0.5
	 *
	 * @param \Parser &$parser
	 *
	 * @return string
	 */
	public static function handle( &$parser ) {

		$langCodes = func_get_args();
		array_shift( $langCodes );
		$prefixedId = array_shift( $langCodes );

		try {
			$entity = static::findEntity( $prefixedId );
			$labels = $entity->getDescriptions();
			if ( empty( $langCodes ) ) {
				return static::makeHtml(
					'wikibase-parserfunction-description',
					static::findTextByGlobalChain( $prefixedId, $labels )
				);
			}
			else {
				return static::makeHtml(
					'wikibase-parserfunction-description',
					static::findTextByLocalChain( $prefixedId, $labels, $langCodes )
				);
			}
		}
		catch ( \MWException $ex ) {
			return $ex->getMessage();
		}
	}
}
