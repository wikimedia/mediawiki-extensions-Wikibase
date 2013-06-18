<?php

namespace Wikibase\Lib;

use InvalidArgumentException;
use OutOfBoundsException;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatterBase;
use ValueFormatters\Result;
use Wikibase\EntityId;

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
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdFormatter extends ValueFormatterBase {

	/**
	 * @todo The documentation here is wrong. It's currently behaving the other way around.
	 *
	 * Option name for the required prefixmap option.
	 * The value of this option should be an array of
	 * prefixes (string) pointing to the entity type
	 * (string) they map to.
	 *
	 * @since 0.4
	 */
	const OPT_PREFIX_MAP = 'prefixmap';

	/**
	 * @since 0.4
	 *
	 * @param FormatterOptions $options
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( FormatterOptions $options ) {
		parent::__construct( $options );

		// TODO: figure out if we want to require this option or not
		//$this->requireOption( self::OPT_PREFIX_MAP );
		$this->defaultOption( self::OPT_PREFIX_MAP, array() );

		if ( !is_array( $this->getOption( self::OPT_PREFIX_MAP ) ) ) {
			throw new InvalidArgumentException( 'The prefix map option needs to be set to an array' );
		}
	}

	/**
	 * Format an EntityId data value
	 *
	 * @since 0.4
	 *
	 * @param EntityId $value The ID to format
	 *
	 * @return string
	 * @throws InvalidArgumentException
	 * @throws OutOfBoundsException
	 */
	public function format( $value ) {
		if ( !( $value instanceof EntityId ) ) {
			throw new InvalidArgumentException( 'Data value type mismatch. Expected an EntityId.' );
		}

		$prefixMap = $this->getOption( self::OPT_PREFIX_MAP );
		$entityType = $value->getEntityType();

		if ( array_key_exists( $entityType, $prefixMap ) ) {
			$entityTypePrefix = $prefixMap[$value->getEntityType()];

			return $entityTypePrefix . $value->getNumericId();
		}

		throw new OutOfBoundsException( "Entity type '$entityType' not found" );
	}

}

