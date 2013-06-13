<?php

namespace Wikibase;

use DataTypes\DataTypeFactory;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\WikiBaseTypeBuilders;

/**
 * Application registry for Wikibase Lib.
 *
 * TODO: migrate out this class; code should be in client or repo and
 * use their respective settings. Same rationale as for moving settings out of lib.
 *
 * @deprecated
 *
 * NOTE:
 * This application registry is a workaround for design problems in existing code.
 * It should only be used to improve existing usage of code and ideally just be
 * a stepping stone towards using proper dependency injection where possible.
 * This means you should be very careful when adding new components to the registry.
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
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class LibRegistry {

	/**
	 * @since 0.4
	 *
	 * @var SettingsArray
	 */
	protected $settings;

	protected $dataTypeFactory = null;

	/**
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 */
	public function __construct( SettingsArray $settings ) {
		$this->settings = $settings;
	}

	/**
	 * @since 0.4
	 *
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		if ( $this->dataTypeFactory === null ) {

			$builders = new WikiBaseTypeBuilders( $this );

			$typeBuilderSpecs = array_intersect_key(
				$builders->getDataTypeBuilders(),
				array_flip( $this->settings->getSetting( 'dataTypes' ) )
			);

			$this->dataTypeFactory = new DataTypeFactory( $typeBuilderSpecs );
		}

		return $this->dataTypeFactory;
	}

	/**
	 * @since 0.4
	 *
	 * @return EntityIdParser
	 */
	public function getEntityIdParser() {
		$options = new ParserOptions( array(
			EntityIdParser::OPT_PREFIX_MAP => $this->settings->getSetting( 'entityPrefixes' )
		) );

		return new EntityIdParser( $options );
	}

	/**
	 * Returns a new instance constructed from global settings.
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @since 0.4
	 *
	 * @return LibRegistry
	 */
	public static function newInstance() {
		return new self( Settings::singleton() );
	}

	// Do not add new stuff here without reading the notice at the top first.

}