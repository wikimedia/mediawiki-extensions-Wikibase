<?php

namespace Wikibase\Client;

use DataTypes\DataTypeFactory;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\ClientStore;
use Wikibase\ClientStoreFactory;
use Wikibase\EntityLookup;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\Test\MockRepository;

/**
 * Top level factory for the WikibaseClient extension.
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
 * @ingroup WikibaseClient
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
final class WikibaseClient {

	/**
	 * @since 0.4
	 *
	 * @var SettingsArray
	 */
	protected $settings;

	protected $dataTypeFactory = null;
	protected $entityIdParser = null;

	protected $isInTestMode;

	/**
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 * @param boolean $inTestMode
	 */
	public function __construct( SettingsArray $settings, $inTestMode ) {
		$this->settings = $settings;
		$this->inTestMode = $inTestMode;
	}

	/**
	 * @since 0.4
	 *
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		if ( $this->dataTypeFactory === null ) {
			global $wgDataTypes;

			$dataTypes = array_intersect_key(
				$wgDataTypes,
				array_flip( $this->settings->getSetting( 'dataTypes' ) )
			);

			$this->dataTypeFactory = new DataTypeFactory( $dataTypes );
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
	 * @since 0.4
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter() {
		$prefixMap = array();

		foreach ( $this->settings->getSetting( 'entityPrefixes' ) as $prefix => $entityType ) {
			$prefixMap[$entityType] = $prefix;
		}

		$options = new FormatterOptions( array(
			EntityIdFormatter::OPT_PREFIX_MAP => $prefixMap
		) );

		return new EntityIdFormatter( $options );
	}

	/**
	 * @since 0.4
	 *
	 * @param string $languageCode
	 *
	 * @return EntityIdLabelFormatter
	 */
	public function newEntityIdLabelFormatter( $languageCode ) {
		$options = new FormatterOptions( array(
			EntityIdLabelFormatter::OPT_LANG => $languageCode
		) );

		$labelFormatter = new EntityIdLabelFormatter( $options, $this->getEntityLookup() );
		$labelFormatter->setIdFormatter( $this->getEntityIdFormatter() );

		return $labelFormatter;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		if ( $this->inTestMode ) {
			return new MockRepository();
		}

		return $this->getStore()->getEntityLookup();
	}

	/**
	 * @since 0.4
	 *
	 * @return PropertyDataTypeLookup
	 */
	public function newPropertyDataTypeLookup() {
		return new EntityRetrievingDataTypeLookup( $this->getEntityLookup() );
	}

	/**
	 * @since 0.4
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter() {
		return new SnakFormatter(
			$this->newPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			$this->getDataTypeFactory()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return ClientStore
	 */
	public function getStore() {
		return ClientStoreFactory::getStore();
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
	 * @return WikibaseClient
	 */
	public static function newInstance() {
		return new self( Settings::singleton(), defined( 'MW_PHPUNIT_TEST' ) );
	}

}