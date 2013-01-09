<?php

namespace Wikibase;
use ValueParsers\Result;
use ValueParsers\StringValueParser;

/**
 * Parser that parses entity id strings into EntityId objects.
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
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdParser extends StringValueParser {

	// TODO: pass as options
	protected static $prefixMap = array(
		'itemPrefix' => Item::ENTITY_TYPE,
		'propertyPrefix' => Property::ENTITY_TYPE,
		'queryPrefix' => Query::ENTITY_TYPE,
	);

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @since 0.1
	 *
	 * @param string $value
	 *
	 * @return Result
	 */
	public function stringParse( $value ) {
		$idParts = $this->getIdParts( $value );

		if ( count( $idParts ) < 3 || !ctype_digit( $idParts[2] ) ) {
			return Result::newErrorText( 'Not an EntityId' );
		}

		$entityType = $this->getEntityTypeForPrefix( $idParts[1] );

		if ( $entityType === null ) {
			return Result::newErrorText( 'EntityId has an invalid prefix' );
		}

		return Result::newSuccess( new EntityId( $entityType, (int)$idParts[2] ) );
	}

	/**
	 * @since 0.3
	 *
	 * @param string $prefix
	 *
	 * @return string|null
	 */
	protected function getEntityTypeForPrefix( $prefix ) {
		static $typeMap = false;

		if ( $typeMap === false ) {
			$typeMap = array();

			foreach ( self::$prefixMap as $setting => $entityType ) {
				$typeMap[Settings::get( $setting )] = $entityType;
			}
		}

		return array_key_exists( $prefix, $typeMap ) ? $typeMap[$prefix] : null;
	}

	/**
	 * Get individual parts of an id.
	 *
	 * @since 0.3
	 *
	 * @param string $id
	 *
	 * @return array The actual id broken up in prefix, number, hash and fragment and fragment alone
	 */
	protected function getIdParts( $id ) {
		static $regex = false;

		if ( $regex === false ) {
			$prefixes = array();

			foreach ( array_keys( self::$prefixMap ) as $setting ) {
				$prefixes[] = preg_quote( Settings::get( $setting ) );
			}

			$regex = '/^(' . implode( '|', $prefixes ) . '|)(\d+)(#.*|)$/';
		}

		preg_match( $regex, strtolower( $id ), $matches );

		return $matches;
	}

}
