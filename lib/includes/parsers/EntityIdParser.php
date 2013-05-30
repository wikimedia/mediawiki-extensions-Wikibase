<?php

namespace Wikibase\Lib;
use ValueParsers\ParseException;
use ValueParsers\StringValueParser;
use ValueParsers\ParserOptions;
use Wikibase\EntityId;

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
 * @since 0.4
 *
 * @file
 * @ingroup ValueParsers
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
class EntityIdParser extends StringValueParser {

	/**
	 * Option name for the required prefixmap option.
	 * The value of this option should be an array of
	 * prefixes (string) pointing to the entity type
	 * (string) they map to.
	 *
	 * @since 0.4
	 */
	const OPT_PREFIX_MAP = 'prefixmap';

	/**
	 * Cache field that holds the regex used by @see getIdParts
	 *
	 * Note that this caching does not get invalidated when changing the options!
	 *
	 * @since 0.4
	 *
	 * @var string|boolean false
	 */
	protected $regex = false;

	private $prefixMap = null;

	/**
	 * @since 0.4
	 *
	 * @param ParserOptions|null $options
	 *
	 * The OPT_PREFIX_MAP option MUST be set, and prefixes MUST be lower case.
	 *
	 * @todo: make this case insensitive
	 */
	public function __construct( ParserOptions $options = null ) {
		parent::__construct( $options );

		$this->requireOption( self::OPT_PREFIX_MAP );
	}

	protected function getPrefixMap() {
		if ( $this->prefixMap === null ) {
			$this->prefixMap = array();

			foreach ( $this->getOption( self::OPT_PREFIX_MAP ) as $prefix => $type ) {
				$prefix = strtolower( $prefix );
				$this->prefixMap[$prefix] = $type;
			}
		}

		return $this->prefixMap;
	}

	/**
	 * @see StringValueParser::stringParse
	 *
	 * @since 0.4
	 *
	 * @param string $value
	 *
	 * @return EntityId
	 * @throws ParseException
	 */
	protected function stringParse( $value ) {
		$idParts = $this->getIdParts( $value );

		if ( count( $idParts ) < 3 || !ctype_digit( $idParts[2] ) ) {
			throw new ParseException( 'Not an EntityId' );
		}

		$entityType = $this->getEntityTypeForPrefix( $idParts[1] );

		if ( $entityType === null ) {
			throw new ParseException( 'EntityId has an invalid prefix' );
		}

		return new EntityId( $entityType, (int)$idParts[2] );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $prefix the prefix to look up
	 *
	 * @return string|null
	 */
	protected function getEntityTypeForPrefix( $prefix ) {
		$prefix = strtolower( $prefix );
		$prefixMap = $this->getPrefixMap();
		return array_key_exists( $prefix, $prefixMap ) ? $prefixMap[$prefix] : null;
	}

	/**
	 * Get individual parts of an id. All results are converted to lower case.
	 *
	 * @since 0.4
	 *
	 * @param string $id
	 *
	 * @return array The actual id broken up in prefix, number, hash and fragment and fragment alone
	 */
	protected function getIdParts( $id ) {
		if ( $this->regex === false ) {
			$prefixes = array();
			$prefixMap = $this->getPrefixMap();

			foreach ( array_keys( $prefixMap ) as $prefix ) {
				$prefixes[] = preg_quote( $prefix );
			}

			$this->regex = '/^(' . implode( '|', $prefixes ) . '|)(\d+)(#.*|)$/';
		}

		preg_match( $this->regex, strtolower( $id ), $matches );

		return $matches;
	}

}
