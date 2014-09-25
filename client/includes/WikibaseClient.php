<?php

namespace Wikibase\Client;

use DataTypes\DataTypeFactory;
use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Exception;
use Language;
use LogicException;
use MediaWikiSite;
use MWException;
use Site;
use SiteSQLStore;
use SiteStore;
use ValueFormatters\FormatterOptions;
use Wikibase\ChangeHandler;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGenerator;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\ClientStore;
use Wikibase\DataAccess\PropertyParserFunction\RendererFactory;
use Wikibase\DataAccess\PropertyParserFunction\Runner;
use Wikibase\DataAccess\PropertyParserFunction\SnaksFinder;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DirectSqlStore;
use Wikibase\EntityFactory;
use Wikibase\InternalSerialization\DeserializerFactory;
use Wikibase\LangLinkHandler;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\Serializers\ForbiddenSerializer;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\WikibaseDataTypeBuilders;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\NamespaceChecker;
use Wikibase\AffectedPagesFinder;
use Wikibase\Settings;
use Wikibase\SettingsArray;
use Wikibase\StringNormalizer;
use Wikibase\WikiPageUpdater;

/**
 * Top level factory for the WikibaseClient extension.
 *
 * @since 0.4
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
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var Language
	 */
	private $contentLanguage;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory = null;

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser = null;

	/**
	 * @var LanguageFallbackChainFactory
	 */
	private $languageFallbackChainFactory = null;

	/**
	 * @var ClientStore
	 */
	private $store = null;

	/**
	 * @var StringNormalizer
	 */
	private $stringNormalizer;

	/**
	 * @var Site
	 */
	private $site = null;

	/**
	 * @var string
	 */
	private $siteGroup = null;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var OutputFormatValueFormatterFactory
	 */
	private $valueFormatterFactory;

	/**
	 * @var SiteStore
	 */
	private $siteStore;

	/**
	 * @var LangLinkHandler
	 */
	private $langLinkHandler = null;

	/**
	 * @var NamespaceChecker
	 */
	private $namespaceChecker = null;

	/**
	 * @since 0.4
	 *
	 * @param SettingsArray $settings
	 * @param Language $contentLanguage
	 * @param SiteStore $siteStore
	 */
	public function __construct(
		SettingsArray $settings,
		Language $contentLanguage,
		SiteStore $siteStore = null
	) {
		$this->settings = $settings;
		$this->contentLanguage = $contentLanguage;
		$this->siteStore = $siteStore;
	}

	/**
	 * @since 0.4
	 *
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		if ( $this->dataTypeFactory === null ) {
			$urlSchemes = $this->getSettings()->getSetting( 'urlSchemes' );
			$builders = new WikibaseDataTypeBuilders(
				$this->getEntityLookup(),
				$this->getEntityIdParser(),
				$urlSchemes
			);

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
		if ( $this->entityIdParser === null ) {
			//TODO: make the ID builders configurable
			$this->entityIdParser = new DispatchingEntityIdParser( BasicEntityIdParser::getBuilders() );
		}

		return $this->entityIdParser;
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

		return $labelFormatter;
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
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
	 * @param string $format The desired format, use SnakFormatter::FORMAT_XXX
	 * @param FormatterOptions $options
	 *
	 * @return SnakFormatter
	 */
	public function newSnakFormatter( $format = SnakFormatter::FORMAT_PLAIN, FormatterOptions $options ) {
		return $this->getSnakFormatterFactory()->getSnakFormatter( $format, $options );
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
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory(
				defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES
			);
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * Returns an instance of the default store.
	 *
	 * @since 0.1
	 *
	 * @throws Exception
	 * @return ClientStore
	 */
	public function getStore() {
		// NOTE: $repoDatabase is null per default, meaning no direct access to the repo's database.
		// If $repoDatabase is false, the local wiki IS the repository.
		// Otherwise, $repoDatabase needs to be a logical database name that LBFactory understands.
		$repoDatabase = $this->settings->getSetting( 'repoDatabase' );

		if ( $this->store === null ) {
			$this->store = new DirectSqlStore(
				$this->getEntityContentDataCodec(),
				$this->getContentLanguage(),
				$this->getEntityIdParser(),
				$repoDatabase
			);
		}

		return $this->store;
	}

	/**
	 * Overrides the default store to be used in the client app context.
	 * This is intended for use by test cases.
	 *
	 * @param ClientStore|null $store
	 *
	 * @throws LogicException If MW_PHPUNIT_TEST is not defined, to avoid this
	 * method being abused in production code.
	 */
	public function overrideStore( ClientStore $store = null ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new LogicException( 'Overriding the store instance is only supported in test mode' );
		}

		$this->store = $store;
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
	 * @return WikibaseClient
	 */
	private static function newInstance() {
		global $wgContLang;

		return new self( Settings::singleton(), $wgContLang );
	}

	/**
	 * Returns the default instance constructed using newInstance().
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @since 0.4
	 *
	 * @param string $reset Flag: Pass "reset" to reset the default instance
	 *
	 * @return WikibaseClient
	 */
	public static function getDefaultInstance( $reset = 'noreset' ) {
		static $instance = null;

		if ( $instance === null || $reset === 'reset' ) {
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
	 * If the configured site ID is not found in the sites table, a
	 * new Site object is constructed from the configured ID.
	 *
	 * @throws MWException
	 * @return Site
	 */
	public function getSite() {
		if ( $this->site === null ) {
			$globalId = $this->settings->getSetting( 'siteGlobalID' );
			$localId = $this->settings->getSetting( 'siteLocalID' );

			$this->site = $this->getSiteStore()->getSite( $globalId );

			if ( !$this->site ) {
				wfDebugLog( __CLASS__, __FUNCTION__ . ": Unable to resolve site ID '{$globalId}'!" );

				$this->site = new MediaWikiSite();
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
	 */
	public function getLangLinkSiteGroup() {
		$group = $this->settings->getSetting( 'languageLinkSiteGroup' );

		if ( $group === null ) {
			$group = $this->getSiteGroup();
		}

		return $group;
	}

	/**
	 * Gets the site group ID from setting, which if not set then does
	 * lookup in site store.
	 *
	 * @return string
	 */
	private function newSiteGroup() {
		$siteGroup = $this->settings->getSetting( 'siteGroup' );

		if ( !$siteGroup ) {
			$siteId = $this->settings->getSetting( 'siteGlobalID' );

			$site = $this->getSiteStore()->getSite( $siteId );

			if ( !$site ) {
				wfWarn( 'Cannot find site ' . $siteId . ' in sites table' );

				return true;
			}

			$siteGroup = $site->getGroup();
		}

		return $siteGroup;
	}

	/**
	 * Get site group ID
	 *
	 * @return string
	 */
	public function getSiteGroup() {
		if ( !$this->siteGroup ) {
			$this->siteGroup = $this->newSiteGroup();
		}

		return $this->siteGroup;
	}

	/**
	 * Returns a OutputFormatSnakFormatterFactory the provides SnakFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatSnakFormatterFactory
	 */
	public function getSnakFormatterFactory() {
		if ( !$this->snakFormatterFactory ) {
			$this->snakFormatterFactory = $this->newSnakFormatterFactory();
		}

		return $this->snakFormatterFactory;
	}

	/**
	 * @return OutputFormatSnakFormatterFactory
	 */
	private function newSnakFormatterFactory() {
		$valueFormatterBuilders = new WikibaseValueFormatterBuilders(
			$this->getEntityLookup(),
			$this->contentLanguage
		);

		$builders = new WikibaseSnakFormatterBuilders(
			$valueFormatterBuilders,
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory()
		);

		$factory = new OutputFormatSnakFormatterFactory( $builders->getSnakFormatterBuildersForFormats() );

		return $factory;
	}

	/**
	 * Returns a OutputFormatValueFormatterFactory the provides ValueFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatValueFormatterFactory
	 */
	public function getValueFormatterFactory() {
		if ( !$this->valueFormatterFactory ) {
			$this->valueFormatterFactory = $this->newValueFormatterFactory();
		}

		return $this->valueFormatterFactory;
	}

	/**
	 * @return OutputFormatValueFormatterFactory
	 */
	private function newValueFormatterFactory() {
		$builders = new WikibaseValueFormatterBuilders(
			$this->getEntityLookup(),
			$this->contentLanguage
		);

		$factory = new OutputFormatValueFormatterFactory( $builders->getValueFormatterBuildersForFormats() );

		return $factory;
	}

	/**
	 * @return NamespaceChecker
	 */
	public function getNamespaceChecker() {
		if ( !$this->namespaceChecker ) {
			$settings = $this->getSettings();

			$this->namespaceChecker = new NamespaceChecker(
				$settings->getSetting( 'excludeNamespaces' ),
				$settings->getSetting( 'namespaces' )
			);
		}

		return $this->namespaceChecker;
	}

	/**
	 * @return LangLinkHandler
	 */
	public function getLangLinkHandler() {
		if ( !$this->langLinkHandler ) {
			$settings = $this->getSettings();

			$this->langLinkHandler = new LangLinkHandler(
				$this->getOtherProjectsSidebarGenerator(),
				$this->getLanguageLinkBadgeDisplay(),
				$settings->getSetting( 'siteGlobalID' ),
				$this->getNamespaceChecker(),
				$this->getStore()->getSiteLinkTable(),
				$this->getStore()->getEntityLookup(),
				$this->getSiteStore(),
				$this->getLangLinkSiteGroup()
			);
		}

		return $this->langLinkHandler;
	}

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	public function getLanguageLinkBadgeDisplay() {
		global $wgLang;

		$badgeClassNames = $this->getSettings()->getSetting( 'badgeClassNames' );

		return new LanguageLinkBadgeDisplay(
			$this->getEntityLookup(),
			is_array( $badgeClassNames ) ? $badgeClassNames : array(),
			$wgLang
		);
	}

	/**
	 * @since 0.5
	 *
	 * @return SiteStore
	 */
	public function getSiteStore() {
		if ( !$this->siteStore ) {
			$this->siteStore = SiteSQLStore::newInstance();
		}

		return $this->siteStore;
	}

	/**
	 * @return EntityFactory
	 */
	public function getEntityFactory() {
		$entityClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Item',
			Property::ENTITY_TYPE => 'Wikibase\DataModel\Entity\Property',
		);

		//TODO: provide a hook or registry for adding more.

		return new EntityFactory( $entityClasses );
	}

	/**
	 * @return EntityContentDataCodec
	 */
	public function getEntityContentDataCodec() {
		// Serialization is not supported on the client, since the client never stores entities.
		$forbiddenSerializer = new ForbiddenSerializer( 'Entity serialization is not supported on the client!' );

		return new EntityContentDataCodec(
			$this->getEntityIdParser(),
			$forbiddenSerializer,
			$this->getInternalEntityDeserializer()
		);
	}

	/**
	 * @return Deserializer
	 */
	public function getInternalEntityDeserializer() {
		return $this->getInternalDeserializerFactory()->newEntityDeserializer();
	}

	/**
	 * @return Deserializer
	 */
	public function getInternalClaimDeserializer() {
		return $this->getInternalDeserializerFactory()->newClaimDeserializer();
	}

	/**
	 * @return DeserializerFactory
	 */
	protected function getInternalDeserializerFactory() {
		return new DeserializerFactory(
			new DataValueDeserializer( array(
				'boolean' => 'DataValues\BooleanValue',
				'number' => 'DataValues\NumberValue',
				'string' => 'DataValues\StringValue',
				'unknown' => 'DataValues\UnknownValue',
				'globecoordinate' => 'DataValues\Geo\Values\GlobeCoordinateValue',
				'monolingualtext' => 'DataValues\MonolingualTextValue',
				'multilingualtext' => 'DataValues\MultilingualTextValue',
				'quantity' => 'DataValues\QuantityValue',
				'time' => 'DataValues\TimeValue',
				'wikibase-entityid' => 'Wikibase\DataModel\Entity\EntityIdValue',
			) ),
			$this->getEntityIdParser()
		);
	}

	/**
	 * @since 0.5
	 *
	 * @return OtherProjectsSidebarGenerator
	 */
	public function getOtherProjectsSidebarGenerator() {
		$settings = $this->getSettings();

		return new OtherProjectsSidebarGenerator(
			$settings->getSetting( 'siteGlobalID' ),
			$this->getStore()->getSiteLinkTable(),
			$this->getSiteStore(),
			$settings->getSetting( 'otherProjectsLinks' )
		);
	}

	/**
	 * @since 0.5
	 *
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		//TODO: take this from a setting or registry.
		$changeClasses = array(
			Item::ENTITY_TYPE => 'Wikibase\ItemChange',
			// Other types of entities will use EntityChange
		);

		return new EntityChangeFactory(
			$this->getStore()->newChangesTable(),
			$this->getEntityFactory(),
			$changeClasses
		);
	}

	/**
	 * @return ParserFunctionRegistrant
	 */
	public function getParserFunctionRegistrant() {
		return new ParserFunctionRegistrant(
			$this->getSettings()->getSetting( 'allowDataTransclusion' )
		);
	}

	/**
	 * @return RendererFactory
	 */
	private function getPropertyParserFunctionRendererFactory() {
		$snaksFinder = new SnaksFinder(
			$this->getEntityLookup(),
			$this->getStore()->getPropertyLabelResolver()
		);

		return new RendererFactory(
			$snaksFinder,
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory()
		);
	}

	/**
	 * @return Runner
	 */
	public function getPropertyParserFunctionRunner() {
		return new Runner(
			$this->getPropertyParserFunctionRendererFactory(),
			$this->getStore()->getSiteLinkTable(),
			$this->getSettings()->getSetting( 'siteGlobalID' )
		);
	}

	/**
	 * @return OtherProjectsSitesProvider
	 */
	public function getOtherProjectsSitesProvider() {
		return new OtherProjectsSitesProvider(
			$this->getSiteStore(),
			$this->getSite(),
			$this->getSettings()->getSetting( 'specialSiteLinkGroups' )
		);
	}

	/**
	 * @return AffectedPagesFinder
	 */
	public function getAffectedPagesFinder() {
		return new AffectedPagesFinder(
			$this->getStore()->getUsageLookup(),
			$this->getNamespaceChecker(),
			new TitleFactory(),
			$this->settings->getSetting( 'siteGlobalID' ),
			true
		);
	}

	/**
	 * @return ChangeHandler
	 */
	public function getChangeHandler() {
		return new ChangeHandler(
			$this->getEntityChangeFactory(),
			$this->getAffectedPagesFinder(),
			new WikiPageUpdater(),
			$this->getStore()->getEntityRevisionLookup(),
			$this->getSite(),
			$this->getSettings()->getSetting( 'injectRecentChanges' ),
			$this->getSettings()->getSetting( 'allowDataTransclusion' )
		);
	}

}
