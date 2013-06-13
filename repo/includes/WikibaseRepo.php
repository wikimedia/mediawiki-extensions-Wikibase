<?php

namespace Wikibase\Repo;

use DataTypes\DataTypeFactory;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\EntityContentFactory;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\WikiBaseTypeBuilders;
use Wikibase\Settings;
use Wikibase\SettingsArray;

/**
 * Top level factory for the WikibaseRepo extension.
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
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseRepo {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var DataTypeFactory|null
	 */
	private $dataTypeFactory = null;

	/**
	 * @var EntityIdFormatter|null
	 */
	private $idFormatter = null;

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
	 * @return EntityContentFactory
	 */
	public function getEntityContentFactory() {
		$entityNamespaces = $this->settings->getSetting( 'entityNamespaces' );

		return new EntityContentFactory(
			$this->getIdFormatter(),
			is_array( $entityNamespaces ) ? array_keys( $entityNamespaces ) : array()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return EntityIdFormatter
	 */
	public function getIdFormatter() {
		if ( $this->idFormatter === null ) {
			$prefixMap = array();

			foreach ( $this->settings->getSetting( 'entityPrefixes' ) as $prefix => $entityType ) {
				$prefixMap[$entityType] = $prefix;
			}

			$options = new FormatterOptions( array(
				EntityIdFormatter::OPT_PREFIX_MAP => $prefixMap
			) );

			$this->idFormatter = new EntityIdFormatter( $options );
		}

		return $this->idFormatter;
	}

	/**
	 * Returns the base to use when generating URIs for use in RDF output.
	 *
	 * @return string
	 */
	public function getRdfBaseURI() {
		global $wgServer; //TODO: make this configurable

		$uri = $wgServer;
		$uri = preg_replace( '!^//!', 'http://', $uri );
		$uri = $uri . '/entity/';
		return $uri;
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
	 * @since 0.4
	 *
	 * @return SettingsArray
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Returns a new instance constructed from global settings.
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @since 0.4
	 *
	 * @return WikibaseRepo
	 */
	public static function newInstance() {
		return new self( Settings::singleton() );
	}

	/**
	 * Returns the default instance constructed using newInstance().
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @since 0.4
	 *
	 * @return WikibaseRepo
	 */
	public static function getDefaultInstance() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = self::newInstance();
		}

		return $instance;
	}

}
