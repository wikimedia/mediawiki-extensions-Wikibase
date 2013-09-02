<?php

namespace Wikibase\Client;

use DataTypes\DataTypeFactory;
use Language;
use Site;
use Sites;
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
use Wikibase\Lib\OldSnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\Lib\WikibaseDataTypeBuilders;
use Wikibase\RepoLinker;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\StringNormalizer;
use Wikibase\Test\MockRepository;

/**
 * Top level factory for the WikibaseClient extension.
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
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var \Site
	 */
	private $site = null;

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

			$urlSchemes = $this->getSettings()->getSetting( 'urlSchemes' );
			$builders = new WikibaseDataTypeBuilders( $this->getEntityLookup(), $this->getEntityIdParser(), $urlSchemes );

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
		return new EntityIdParser( new ParserOptions() );
	}

	/**
	 * @since 0.4
	 *
	 * @return EntityIdFormatter
	 */
	public function getEntityIdFormatter() {
		return new EntityIdFormatter( new FormatterOptions() );
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
	 * @return OldSnakFormatter
	 */
	public function newSnakFormatter() {
		return new OldSnakFormatter(
			$this->getPropertyDataTypeLookup(),
			new TypedValueFormatter(),
			$this->getDataTypeFactory()
		);
	}

	/**
	 * @since 0.4
	 *
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		if ( $this->stringNormalizer === null ) {
			$this->stringNormalizer = new StringNormalizer();
		}

		return $this->stringNormalizer;
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

	/**
	 * Returns the this client wiki's site object.
	 *
	 * This is taken from the siteGlobalID setting, which defaults
	 * to the wiki's database name.
	 *
	 * If the configured site ID is not found in the Sites list, a
	 * new Site object is constructed from the configured ID.
	 *
	 * @throws \MWException
	 * @return Site
	 */
	public function getSite() {
		if ( $this->site === null ) {
			$globalId = $this->settings->getSetting( 'siteGlobalID' );
			$localId = $this->settings->getSetting( 'siteLocalID' );

			$sites = Sites::singleton();
			$this->site = $sites->getSite( $globalId );

			if ( !$this->site ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Unable to resolve site ID '{$globalId}'!" );

				$this->site = new \MediaWikiSite();
				$this->site->setGlobalId( $globalId );
				$this->site->addLocalId( Site::ID_INTERWIKI, $localId );
				$this->site->addLocalId( Site::ID_EQUIVALENT, $localId );
			}

			if ( !in_array( $localId, $this->site->getLocalIds() ) ) {
				wfDebugLog( __CLASS__, __FUNCTION__
						. ": The configured local id $localId does not match any local ID of site $globalId: "
						. var_export( $this->site->getLocalIds(), true ) );
			}
		}

		return $this->site;
	}

	/**
	 * Returns the site group ID for the group to be used for language links.
	 * This is typically the group the client wiki itself belongs to, but
	 * can be configured to be otherwise using the languageLinkSiteGroup setting.
	 *
	 * @return string
	 * @throws \MWException
	 */
	public function getLangLinkSiteGroup() {
		$group = $this->settings->getSetting( 'languageLinkSiteGroup' );

		if ( $group === null ) {
			$thisSite = $this->getSite();
			$group = $thisSite->getGroup();
		}

		return $group;
	}
}
