<?php

namespace Wikibase\Client;

use DataTypes\DataTypeFactory;
use Language;
use ValueFormatters\FormatterOptions;
use ValueParsers\ParserOptions;
use Wikibase\ClientStore;
use Wikibase\EntityLookup;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EntityIdParser;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Lib\WikibaseDataTypeBuilders;
use Wikibase\RepoLinker;
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
 * @author Daniel Kinzler
 */
final class WikibaseClient {

	/**
	 * @var PropertyDataTypeLookup
	 */
	public $propertyDataTypeLookup;

	/**
	 * @since 0.4
	 *
	 * @var SettingsArray
	 */
	protected $settings;

	/**
	 * @since 0.4
	 *
	 * @var Language
	 */
	protected $contentLanguage;

	protected $dataTypeFactory = null;
	protected $entityIdParser = null;
	protected $languageFallbackChainFactory = null;

	protected $isInTestMode;

	private $storeInstances = array();

	/**
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 * @param Language      $contentLanguage
	 * @param               $inTestMode
	 */
	public function __construct( SettingsArray $settings, Language $contentLanguage, $inTestMode ) {
		$this->contentLanguage = $contentLanguage;
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

			$builders = new WikibaseDataTypeBuilders( $this->getEntityLookup(), $this->getEntityIdParser() );

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
	public function getPropertyDataTypeLookup() {
		if ( $this->propertyDataTypeLookup === null ) {
			$infoStore = $this->getStore()->getPropertyInfoStore();
			$retrievingLookup = new EntityRetrievingDataTypeLookup( $this->getEntityLookup() );
			$this->propertyDataTypeLookup = new PropertyInfoDataTypeLookup( $infoStore, $retrievingLookup );
		}

		return $this->propertyDataTypeLookup;
	}

	/**
	 * @since 0.4
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter() {
		return new SnakFormatter(
			$this->getPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			$this->getDataTypeFactory()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return RepoLinker
	 */
	public function newRepoLinker() {
		return new RepoLinker(
			$this->settings->getSetting( 'repoUrl' ),
			$this->settings->getSetting( 'repoArticlePath' ),
			$this->settings->getSetting( 'repoScriptPath' ),
			$this->settings->getSetting( 'repoNamespaces' )
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * Returns an instance of the default store, or an alternate store
	 * if so specified with the $store argument.
	 *
	 * @since 0.1
	 *
	 * @param boolean|string $store
	 * @param string         $reset set to 'reset' to force a fresh instance to be returned.
	 *
	 * @return ClientStore
	 */
	public function getStore( $store = false, $reset = 'no' ) {
		global $wgWBClientStores; //XXX: still using a global here

		if ( $store === false || !array_key_exists( $store, $wgWBClientStores ) ) {
			$store = $this->settings->getSetting( 'defaultClientStore' ); // still false per default
		}

		//NOTE: $repoDatabase is null per default, meaning no direct access to the repo's database.
		//      If $repoDatabase is false, the local wiki IS the repository.
		//      Otherwise, $repoDatabase needs to be a logical database name that LBFactory understands.
		$repoDatabase = $this->settings->getSetting( 'repoDatabase' );

		if ( !$store ) {
			//XXX: this is a rather ugly "magic" default.
			if ( $repoDatabase !== null ) {
				$store = 'DirectSqlStore';
			} else {
				$store = 'CachingSqlStore';
			}
		}

		$class = $wgWBClientStores[$store];

		if ( $reset !== true && $reset !== 'reset'
			&& isset( $this->storeInstances[$store] ) ) {

			return $this->storeInstances[$store];
		}

		$instance = new $class(
			$this->getContentLanguage(),
			$repoDatabase
		);

		assert( $instance instanceof ClientStore );

		$this->storeInstances[$store] = $instance;
		return $instance;
	}

	/**
	 * @since 0.4
	 *
	 * @return Language
	 */
	public function getContentLanguage() {
		return $this->contentLanguage;
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
	protected static function newInstance() {
		global $wgContLang;

		return new self(
			Settings::singleton(),
			$wgContLang,
			defined( 'MW_PHPUNIT_TEST' ) );
	}

	/**
	 * Returns the default instance constructed using newInstance().
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @since 0.4
	 *
	 * @return WikibaseClient
	 */
	public static function getDefaultInstance() {
		static $instance = null;

		if ( $instance === null ) {
			$instance = self::newInstance();
		}

		return $instance;
	}

}
