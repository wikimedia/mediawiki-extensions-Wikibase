<?php

namespace Wikibase\Client;

use DataTypes\DataTypeFactory;
use DataValues\Deserializers\DataValueDeserializer;
use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\MonolingualTextValue;
use DataValues\QuantityValue;
use DataValues\Serializers\DataValueSerializer;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnknownValue;
use Deserializers\Deserializer;
use Deserializers\DispatchingDeserializer;
use Exception;
use Hooks;
use Http;
use JobQueueGroup;
use Language;
use LogicException;
use MediaWiki\MediaWikiServices;
use MediaWikiSite;
use MWException;
use Serializers\DispatchingSerializer;
use Serializers\Serializer;
use Site;
use SiteLookup;
use StubObject;
use Title;
use Wikibase\Client\Changes\AffectedPagesFinder;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\Changes\ChangeRunCoalescer;
use Wikibase\Client\Changes\WikiPageUpdater;
use Wikibase\Client\DataAccess\DataAccessSnakFormatterFactory;
use Wikibase\Client\DataAccess\ClientSiteLinkTitleLookup;
use Wikibase\Client\DataAccess\PropertyIdResolver;
use Wikibase\Client\DataAccess\PropertyParserFunction\StatementGroupRendererFactory;
use Wikibase\Client\DataAccess\PropertyParserFunction\Runner;
use Wikibase\Client\ParserOutput\ClientParserOutputDataUpdater;
use Wikibase\Client\RecentChanges\RecentChangeFactory;
use Wikibase\Client\Serializer\ForbiddenSerializer;
use Wikibase\Client\Store\RepositoryServiceContainerFactory;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\PrefixMappingEntityIdParserFactory;
use Wikibase\DataModel\Services\Lookup\RestrictedEntityLookup;
use Wikibase\Client\DataAccess\SnaksFinder;
use Wikibase\Client\Hooks\LanguageLinkBadgeDisplay;
use Wikibase\Client\Hooks\OtherProjectsSidebarGeneratorFactory;
use Wikibase\Client\Hooks\ParserFunctionRegistrant;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\ClientStore;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Entity\DispatchingEntityIdParser;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\DirectSqlStore;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\ItemChange;
use Wikibase\LangLinkHandler;
use Wikibase\LanguageFallbackChain;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityIdComposer;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Interactors\TermSearchInteractor;
use Wikibase\Lib\RepositoryDefinitions;
use Wikibase\Lib\Serialization\RepositorySpecificDataValueDeserializerFactory;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\MediaWikiContentLanguages;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\OutputFormatValueFormatterFactory;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\FallbackPropertyOrderProvider;
use Wikibase\Lib\Store\HttpUrlPropertyOrderProvider;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\PropertyOrderProvider;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\WikibaseValueFormatterBuilders;
use Wikibase\NamespaceChecker;
use Wikibase\SettingsArray;
use Wikibase\Client\RecentChanges\SiteLinkCommentCreator;
use Wikibase\StringNormalizer;

/**
 * Top level factory for the WikibaseClient extension.
 *
 * @license GPL-2.0+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 */
final class WikibaseClient {

	/**
	 * @var SettingsArray
	 */
	private $settings;

	/**
	 * @var SiteLookup
	 */
	private $siteLookup;

	/**
	 * @var EntityDataRetrievalServiceFactory
	 */
	private $entityDataRetrievalServiceFactory;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $propertyDataTypeLookup = null;

	/**
	 * @var DataTypeFactory|null
	 */
	private $dataTypeFactory = null;

	/**
	 * @var Deserializer|null
	 */
	private $entityDeserializer = null;

	/**
	 * @var Serializer[]
	 */
	private $entitySerializers = array();

	/**
	 * @var EntityIdParser|null
	 */
	private $entityIdParser = null;

	/**
	 * @var EntityIdComposer|null
	 */
	private $entityIdComposer = null;

	/**
	 * @var LanguageFallbackChainFactory|null
	 */
	private $languageFallbackChainFactory = null;

	/**
	 * @var ClientStore|null
	 */
	private $store = null;

	/**
	 * @var StringNormalizer|null
	 */
	private $stringNormalizer = null;

	/**
	 * @var Site|null
	 */
	private $site = null;

	/**
	 * @var string|null
	 */
	private $siteGroup = null;

	/**
	 * @var OutputFormatSnakFormatterFactory|null
	 */
	private $snakFormatterFactory = null;

	/**
	 * @var OutputFormatValueFormatterFactory|null
	 */
	private $valueFormatterFactory = null;

	/**
	 * @var LangLinkHandler|null
	 */
	private $langLinkHandler = null;

	/**
	 * @var ClientParserOutputDataUpdater|null
	 */
	private $parserOutputDataUpdater = null;

	/**
	 * @var NamespaceChecker|null
	 */
	private $namespaceChecker = null;

	/**
	 * @var RestrictedEntityLookup|null
	 */
	private $restrictedEntityLookup = null;

	/**
	 * @var DataTypeDefinitions
	 */
	private $dataTypeDefinitions;

	/**
	 * @var EntityTypeDefinitions
	 */
	private $entityTypeDefinitions;

	/**
	 * @var RepositoryDefinitions
	 */
	private $repositoryDefinitions;

	/**
	 * @var PrefetchingTermLookup|null
	 */
	private $termLookup = null;

	/**
	 * @var EntityNamespaceLookup|null
	 */
	private $entityNamespaceLookup = null;

	/**
	 * @var PropertyOrderProvider|null
	 */
	private $propertyOrderProvider = null;

	/**
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders() {
		static $builders;

		if ( $builders === null ) {
			$builders = self::getDefaultInstance()->newWikibaseValueFormatterBuilders();
		}

		return $builders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders() {
		$settings = $this->getSettings();
		$entityTitleLookup = new ClientSiteLinkTitleLookup(
			$this->getStore()->getSiteLinkLookup(),
			$settings->getSetting( 'siteGlobalID' )
		);

		return new WikibaseValueFormatterBuilders(
			$this->getContentLanguage(),
			new FormatterLabelDescriptionLookupFactory( $this->getTermLookup() ),
			new LanguageNameLookup( $this->getUserLanguage()->getCode() ),
			$this->getRepoItemUriParser(),
			$settings->getSetting( 'geoShapeStorageFrontendUrl' ),
			$entityTitleLookup
		);
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseClient.datatypes.php only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	public static function getDefaultSnakFormatterBuilders() {
		static $builders;

		if ( $builders === null ) {
			$builders = self::getDefaultInstance()->newWikibaseSnakFormatterBuilders(
				self::getDefaultValueFormatterBuilders()
			);
		}

		return $builders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseClient::getSnakFormatterFactory() instead!
	 *
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	private function newWikibaseSnakFormatterBuilders( WikibaseValueFormatterBuilders $valueFormatterBuilders ) {
		return new WikibaseSnakFormatterBuilders(
			$valueFormatterBuilders,
			$this->getStore()->getPropertyInfoLookup(),
			$this->getPropertyDataTypeLookup(),
			$this->getDataTypeFactory()
		);
	}

	/**
	 * @param SettingsArray $settings
	 * @param DataTypeDefinitions $dataTypeDefinitions
	 * @param EntityTypeDefinitions $entityTypeDefinitions
	 * @param RepositoryDefinitions $repositoryDefinitions
	 * @param SiteLookup $siteLookup
	 */
	public function __construct(
		SettingsArray $settings,
		DataTypeDefinitions $dataTypeDefinitions,
		EntityTypeDefinitions $entityTypeDefinitions,
		RepositoryDefinitions $repositoryDefinitions,
		SiteLookup $siteLookup
	) {
		$this->settings = $settings;
		$this->dataTypeDefinitions = $dataTypeDefinitions;
		$this->entityTypeDefinitions = $entityTypeDefinitions;
		$this->repositoryDefinitions = $repositoryDefinitions;
		$this->siteLookup = $siteLookup;
	}

	/**
	 * @return DataTypeFactory
	 */
	public function getDataTypeFactory() {
		if ( $this->dataTypeFactory === null ) {
			$this->dataTypeFactory = new DataTypeFactory( $this->dataTypeDefinitions->getValueTypes() );
		}

		return $this->dataTypeFactory;
	}

	/**
	 * @return EntityIdParser
	 */
	public function getEntityIdParser() {
		if ( $this->entityIdParser === null ) {
			$this->entityIdParser = new DispatchingEntityIdParser(
				$this->entityTypeDefinitions->getEntityIdBuilders()
			);
		}

		return $this->entityIdParser;
	}

	/**
	 * @return EntityIdComposer
	 */
	public function getEntityIdComposer() {
		if ( $this->entityIdComposer === null ) {
			$this->entityIdComposer = new EntityIdComposer(
				$this->entityTypeDefinitions->getEntityIdComposers()
			);
		}

		return $this->entityIdComposer;
	}

	/**
	 * @return EntityDataRetrievalServiceFactory
	 */
	public function getEntityDataRetrievalServiceFactory() {
		if ( $this->entityDataRetrievalServiceFactory === null ) {
			$factory = new DispatchingServiceFactory(
				$this->getRepositoryServiceContainerFactory(),
				$this->repositoryDefinitions
			);
			$factory->loadWiringFiles( $this->settings->getSetting( 'dispatchingServiceWiringFiles' ) );

			$this->entityDataRetrievalServiceFactory = $factory;
		}

		return $this->entityDataRetrievalServiceFactory;
	}

	private function getRepositoryServiceContainerFactory() {
		$idParserFactory = new PrefixMappingEntityIdParserFactory(
			$this->getEntityIdParser(),
			$this->repositoryDefinitions->getPrefixMappings()
		);

		return new RepositoryServiceContainerFactory(
			$idParserFactory,
			new RepositorySpecificDataValueDeserializerFactory( $idParserFactory ),
			$this->repositoryDefinitions->getDatabaseNames(),
			$this->getSettings()->getSetting( 'repositoryServiceWiringFiles' ),
			$this
		);
	}

	/**
	 * @return EntityLookup
	 */
	private function getEntityLookup() {
		return $this->getStore()->getEntityLookup();
	}

	/**
	 * @return array[]
	 */
	private static function getDefaultEntityTypes() {
		return require __DIR__ . '/../../lib/WikibaseLib.entitytypes.php';
	}

	/**
	 * @return TermBuffer
	 */
	public function getTermBuffer() {
		return $this->getPrefetchingTermLookup();
	}

	/**
	 * @return TermLookup
	 */
	public function getTermLookup() {
		return $this->getPrefetchingTermLookup();
	}

	/**
	 * @return PrefetchingTermLookup
	 */
	private function getPrefetchingTermLookup() {
		if ( !$this->termLookup ) {
			$this->termLookup = $this->getEntityDataRetrievalServiceFactory()->getTermBuffer();
		}

		return $this->termLookup;
	}

	/**
	 * @param string $displayLanguageCode
	 *
	 * XXX: This is not used by client itself, but is used by ArticlePlaceholder!
	 *
	 * @return TermSearchInteractor
	 */
	public function newTermSearchInteractor( $displayLanguageCode ) {
		return $this->getEntityDataRetrievalServiceFactory()->getTermSearchInteractorFactory()
			->newInteractor( $displayLanguageCode );
	}

	/**
	 * @return PropertyDataTypeLookup
	 */
	public function getPropertyDataTypeLookup() {
		if ( $this->propertyDataTypeLookup === null ) {
			$infoLookup = $this->getStore()->getPropertyInfoLookup();
			$retrievingLookup = new EntityRetrievingDataTypeLookup( $this->getEntityLookup() );
			$this->propertyDataTypeLookup = new PropertyInfoDataTypeLookup( $infoLookup, $retrievingLookup );
		}

		return $this->propertyDataTypeLookup;
	}

	/**
	 * @return StringNormalizer
	 */
	public function getStringNormalizer() {
		if ( $this->stringNormalizer === null ) {
			$this->stringNormalizer = new StringNormalizer();
		}

		return $this->stringNormalizer;
	}

	/**
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
	 * @return LanguageFallbackChainFactory
	 */
	public function getLanguageFallbackChainFactory() {
		if ( $this->languageFallbackChainFactory === null ) {
			$this->languageFallbackChainFactory = new LanguageFallbackChainFactory();
		}

		return $this->languageFallbackChainFactory;
	}

	/**
	 * @return LanguageFallbackLabelDescriptionLookupFactory
	 */
	public function getLanguageFallbackLabelDescriptionLookupFactory() {
		return new LanguageFallbackLabelDescriptionLookupFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getTermLookup(),
			$this->getTermBuffer()
		);
	}

	/**
	 * Returns an instance of the default store.
	 *
	 * @throws Exception
	 * @return ClientStore
	 */
	public function getStore() {
		if ( $this->store === null ) {
			// NOTE: $repoDatabase is null per default, meaning no direct access to the repo's
			// database. If $repoDatabase is false, the local wiki IS the repository. Otherwise,
			// $repoDatabase needs to be a logical database name that LBFactory understands.
			$repoDatabase = $this->settings->getSetting( 'repoDatabase' );
			$this->store = new DirectSqlStore(
				$this->getEntityChangeFactory(),
				$this->getEntityContentDataCodec(),
				$this->getEntityIdParser(),
				$this->getEntityIdComposer(),
				$this->getEntityNamespaceLookup(),
				$this->getEntityDataRetrievalServiceFactory(),
				$repoDatabase,
				$this->getContentLanguage()->getCode()
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
	 * Overrides the TermLookup to be used.
	 * This is intended for use by test cases.
	 *
	 * @param TermLookup|null $lookup
	 *
	 * @throws LogicException If MW_PHPUNIT_TEST is not defined, to avoid this
	 * method being abused in production code.
	 */
	public function overrideTermLookup( TermLookup $lookup = null ) {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new LogicException( 'Overriding TermLookup is only supported in test mode' );
		}

		$this->termLookup = $lookup;
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	public function getContentLanguage() {
		global $wgContLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgContLang in the constructor, because it may still be null
		// when WikibaseClient is initialized. In particular, the language object may not yet
		// be there when the SetupAfterCache hook is run during bootstrapping.

		if ( !$wgContLang ) {
			throw new MWException( 'Premature access: $wgContLang is not yet initialized!' );
		}

		StubObject::unstub( $wgContLang );
		return $wgContLang;
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	private function getUserLanguage() {
		global $wgLang;

		// TODO: define a LanguageProvider service instead of using a global directly.
		// NOTE: we cannot inject $wgLang in the constructor, because it may still be null
		// when WikibaseClient is initialized. In particular, the language object may not yet
		// be there when the SetupAfterCache hook is run during bootstrapping.

		if ( !$wgLang ) {
			throw new MWException( 'Premature access: $wgLang is not yet initialized!' );
		}

		StubObject::unstub( $wgLang );
		return $wgLang;
	}

	/**
	 * @return SettingsArray
	 */
	public function getSettings() {
		return $this->settings;
	}

	/**
	 * Returns the repo's settings array IF the local wiki also acts as a repository.
	 * If the local wiki is not a repository, this method returns null.
	 *
	 * This is intended to be used ONLY to allow client settings to default to local repo
	 * settings in WikibaseClient.default.php.
	 *
	 * @return SettingsArray|null
	 */
	public function getRepoSettings() {
		if ( defined( 'WB_VERSION' ) ) {
			return \Wikibase\Repo\WikibaseRepo::getDefaultInstance()->getSettings();
		} else {
			return null;
		}
	}

	/**
	 * Returns a new instance constructed from global settings.
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @throws MWException
	 * @return WikibaseClient
	 */
	private static function newInstance() {
		global $wgWBClientSettings, $wgWBClientDataTypes;

		if ( !is_array( $wgWBClientDataTypes ) ) {
			throw new MWException( '$wgWBClientDataTypes must be array. '
				. 'Maybe you forgot to require WikibaseClient.php in your LocalSettings.php?' );
		}

		$dataTypeDefinitions = $wgWBClientDataTypes;
		Hooks::run( 'WikibaseClientDataTypes', array( &$dataTypeDefinitions ) );

		$entityTypeDefinitions = self::getDefaultEntityTypes();
		Hooks::run( 'WikibaseClientEntityTypes', array( &$entityTypeDefinitions ) );

		$settings = new SettingsArray( $wgWBClientSettings );

		return new self(
			$settings,
			new DataTypeDefinitions(
				$dataTypeDefinitions,
				$settings->getSetting( 'disabledDataTypes' )
			),
			new EntityTypeDefinitions( $entityTypeDefinitions ),
			self::getRepositoryDefinitionsFromSettings( $settings ),
			MediaWikiServices::getInstance()->getSiteLookup()
		);
	}

	/**
	 * @param SettingsArray $settings
	 *
	 * @return RepositoryDefinitions
	 */
	private static function getRepositoryDefinitionsFromSettings( SettingsArray $settings ) {
		// FIXME: It might no longer be needed to check different settings (repoDatabase vs foreignRepositories)
		// once repository settings are unified, see: T153767.
		$definitions = [ '' => [
			'database' => $settings->getSetting( 'repoDatabase' ),
			'prefix-mapping' => [ '' => '' ],
			'entity-types' => array_keys( $settings->getSetting( 'repoNamespaces' ) ),
		] ];

		foreach ( $settings->getSetting( 'foreignRepositories' ) as $repository => $repositorySettings ) {
			$definitions[$repository] = [
				'database' => $repositorySettings['repoDatabase'],
				'entity-types' => $repositorySettings['supportedEntityTypes'],
				'prefix-mapping' => $repositorySettings['prefixMapping'],
			];
		}

		return new RepositoryDefinitions( $definitions );
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
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

			$this->site = $this->siteLookup->getSite( $globalId );

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

			$site = $this->siteLookup->getSite( $siteId );

			if ( !$site ) {
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
		if ( $this->siteGroup === null ) {
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
		if ( $this->snakFormatterFactory === null ) {
			$this->snakFormatterFactory = new OutputFormatSnakFormatterFactory(
				$this->dataTypeDefinitions->getSnakFormatterFactoryCallbacks(),
				$this->getValueFormatterFactory(),
				$this->getPropertyDataTypeLookup(),
				$this->getDataTypeFactory()
			);
		}

		return $this->snakFormatterFactory;
	}

	/**
	 * Returns a OutputFormatValueFormatterFactory the provides ValueFormatters
	 * for different output formats.
	 *
	 * @return OutputFormatValueFormatterFactory
	 */
	public function getValueFormatterFactory() {
		if ( $this->valueFormatterFactory === null ) {
			$this->valueFormatterFactory = new OutputFormatValueFormatterFactory(
				$this->dataTypeDefinitions->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				$this->getContentLanguage(),
				$this->getLanguageFallbackChainFactory()
			);
		}

		return $this->valueFormatterFactory;
	}

	/**
	 * @return EntityIdParser
	 */
	private function getRepoItemUriParser() {
		return new SuffixEntityIdParser(
			$this->getSettings()->getSetting( 'repoConceptBaseUri' ),
			new ItemIdParser()
		);
	}

	/**
	 * @return NamespaceChecker
	 */
	public function getNamespaceChecker() {
		if ( $this->namespaceChecker === null ) {
			$this->namespaceChecker = new NamespaceChecker(
				$this->settings->getSetting( 'excludeNamespaces' ),
				$this->settings->getSetting( 'namespaces' )
			);
		}

		return $this->namespaceChecker;
	}

	/**
	 * @return LangLinkHandler
	 */
	public function getLangLinkHandler() {
		if ( $this->langLinkHandler === null ) {
			$this->langLinkHandler = new LangLinkHandler(
				$this->getLanguageLinkBadgeDisplay(),
				$this->getNamespaceChecker(),
				$this->getStore()->getSiteLinkLookup(),
				$this->getStore()->getEntityLookup(),
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' ),
				$this->getLangLinkSiteGroup()
			);
		}

		return $this->langLinkHandler;
	}

	/**
	 * @return ClientParserOutputDataUpdater
	 */
	public function getParserOutputDataUpdater() {
		if ( $this->parserOutputDataUpdater === null ) {
			$this->parserOutputDataUpdater = new ClientParserOutputDataUpdater(
				$this->getOtherProjectsSidebarGeneratorFactory(),
				$this->getStore()->getSiteLinkLookup(),
				$this->getStore()->getEntityLookup(),
				$this->settings->getSetting( 'siteGlobalID' )
			);
		}

		return $this->parserOutputDataUpdater;
	}

	/**
	 * @return LanguageLinkBadgeDisplay
	 */
	public function getLanguageLinkBadgeDisplay() {
		$labelDescriptionLookupFactory = $this->getLanguageFallbackLabelDescriptionLookupFactory();
		$badgeClassNames = $this->settings->getSetting( 'badgeClassNames' );
		$lang = $this->getUserLanguage();

		return new LanguageLinkBadgeDisplay(
			$labelDescriptionLookupFactory->newLabelDescriptionLookup( $lang ),
			is_array( $badgeClassNames ) ? $badgeClassNames : array(),
			$lang
		);
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
			$this->getInternalFormatEntityDeserializer(),
			$this->getSettings()->getSetting( 'maxSerializedEntitySize' ) * 1024
		);
	}

	/**
	 * @return DeserializerFactory
	 */
	public function getExternalFormatDeserializerFactory() {
		return new DeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser()
		);
	}

	/**
	 * @return InternalDeserializerFactory
	 */
	public function getInternalFormatDeserializerFactory() {
		return new InternalDeserializerFactory(
			$this->getDataValueDeserializer(),
			$this->getEntityIdParser(),
			$this->getExternalFormatEntityDeserializer()
		);
	}

	/**
	 * @return DispatchingDeserializer
	 */
	private function getExternalFormatEntityDeserializer() {
		if ( $this->entityDeserializer === null ) {
			$deserializerFactoryCallbacks = $this->getEntityDeserializerFactoryCallbacks();
			$deserializerFactory = $this->getExternalFormatDeserializerFactory();
			$deserializers = array();

			foreach ( $deserializerFactoryCallbacks as $callback ) {
				$deserializers[] = call_user_func( $callback, $deserializerFactory );
			}

			$this->entityDeserializer = new DispatchingDeserializer( $deserializers );
		}

		return $this->entityDeserializer;
	}

	/**
	 * Returns a deserializer to deserialize entities in both current and legacy serialization.
	 *
	 * @return Deserializer
	 */
	public function getInternalFormatEntityDeserializer() {
		return $this->getInternalFormatDeserializerFactory()->newEntityDeserializer();
	}

	/**
	 * Returns a deserializer to deserialize statements in both current and legacy serialization.
	 *
	 * @return Deserializer
	 */
	public function getInternalFormatStatementDeserializer() {
		return $this->getInternalFormatDeserializerFactory()->newStatementDeserializer();
	}

	/**
	 * @return callable[]
	 */
	public function getEntityDeserializerFactoryCallbacks() {
		return $this->entityTypeDefinitions->getDeserializerFactoryCallbacks();
	}

	/**
	 * @param int $options bitwise combination of the SerializerFactory::OPTION_ flags
	 *
	 * @return Serializer
	 */
	public function getEntitySerializer( $options = SerializerFactory::OPTION_DEFAULT ) {
		if ( !isset( $this->entitySerializers[$options] ) ) {
			$serializerFactoryCallbacks = $this->entityTypeDefinitions->getSerializerFactoryCallbacks();
			$serializerFactory = new SerializerFactory( new DataValueSerializer(), $options );
			$serializers = array();

			foreach ( $serializerFactoryCallbacks as $callback ) {
				$serializers[] = call_user_func( $callback, $serializerFactory );
			}

			$this->entitySerializers[$options] = new DispatchingSerializer( $serializers );
		}

		return $this->entitySerializers[$options];
	}

	/**
	 * @return DataValueDeserializer
	 */
	private function getDataValueDeserializer() {
		return new DataValueDeserializer( array(
			'string' => StringValue::class,
			'unknown' => UnknownValue::class,
			'globecoordinate' => GlobeCoordinateValue::class,
			'monolingualtext' => MonolingualTextValue::class,
			'quantity' => QuantityValue::class,
			'time' => TimeValue::class,
			'wikibase-entityid' => function( $value ) {
				return isset( $value['id'] )
					? new EntityIdValue( $this->getEntityIdParser()->parse( $value['id'] ) )
					: EntityIdValue::newFromArray( $value );
			},
		) );
	}

	/**
	 * @return OtherProjectsSidebarGeneratorFactory
	 */
	public function getOtherProjectsSidebarGeneratorFactory() {
		return new OtherProjectsSidebarGeneratorFactory(
			$this->settings,
			$this->getStore()->getSiteLinkLookup(),
			$this->siteLookup
		);
	}

	/**
	 * @return EntityChangeFactory
	 */
	public function getEntityChangeFactory() {
		//TODO: take this from a setting or registry.
		$changeClasses = array(
			Item::ENTITY_TYPE => ItemChange::class,
			// Other types of entities will use EntityChange
		);

		return new EntityChangeFactory(
			$this->getEntityDiffer(),
			$changeClasses
		);
	}

	/**
	 * @return EntityDiffer
	 */
	public function getEntityDiffer() {
		$strategieBuilders = $this->entityTypeDefinitions->getEntityDifferStrategyBuilders();
		$entityDiffer = new EntityDiffer();
		foreach ( $strategieBuilders as $strategyBuilder ) {
			$entityDiffer->registerEntityDifferStrategy( call_user_func( $strategyBuilder ) );
		}
		return $entityDiffer;
	}

	/**
	 * @return ParserFunctionRegistrant
	 */
	public function getParserFunctionRegistrant() {
		return new ParserFunctionRegistrant(
			$this->settings->getSetting( 'allowDataTransclusion' )
		);
	}

	/**
	 * @return StatementGroupRendererFactory
	 */
	private function getStatementGroupRendererFactory() {
		$entityLookup = $this->getRestrictedEntityLookup();

		$propertyIdResolver = new PropertyIdResolver(
			$entityLookup,
			$this->getStore()->getPropertyLabelResolver()
		);

		return new StatementGroupRendererFactory(
			$propertyIdResolver,
			new SnaksFinder(),
			$entityLookup,
			$this->getDataAccessSnakFormatterFactory(),
			$this->getSettings()->getSetting( 'allowDataAccessInUserLanguage' )
		);
	}

	/**
	 * @return DataAccessSnakFormatterFactory
	 */
	public function getDataAccessSnakFormatterFactory() {
		return new DataAccessSnakFormatterFactory(
			$this->getLanguageFallbackChainFactory(),
			$this->getSnakFormatterFactory(),
			$this->getPropertyDataTypeLookup()
		);
	}

	/**
	 * @return Runner
	 */
	public function getPropertyParserFunctionRunner() {
		return new Runner(
			$this->getStatementGroupRendererFactory(),
			$this->getStore()->getSiteLinkLookup(),
			$this->getEntityIdParser(),
			$this->getRestrictedEntityLookup(),
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->settings->getSetting( 'allowArbitraryDataAccess' )
		);
	}

	/**
	 * @return OtherProjectsSitesProvider
	 */
	public function getOtherProjectsSitesProvider() {
		return new CachingOtherProjectsSitesProvider(
			new OtherProjectsSitesGenerator(
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' ),
				$this->settings->getSetting( 'specialSiteLinkGroups' )
			),
			// TODO: Make configurable? Should be similar, maybe identical to sharedCacheType and
			// sharedCacheDuration, but can not reuse these because this here is not shared.
			wfGetMainCache(),
			60 * 60
		);
	}

	/**
	 * @return AffectedPagesFinder
	 */
	public function getAffectedPagesFinder() {
		return new AffectedPagesFinder(
			$this->getStore()->getUsageLookup(),
			new TitleFactory(),
			$this->settings->getSetting( 'siteGlobalID' ),
			$this->getContentLanguage()->getCode()
		);
	}

	/**
	 * @return ChangeHandler
	 */
	public function getChangeHandler() {
		$pageUpdater = new WikiPageUpdater(
			JobQueueGroup::singleton(),
			$this->getRecentChangeFactory(),
			$this->getStore()->getRecentChangesDuplicateDetector(),
			MediaWikiServices::getInstance()->getStatsdDataFactory()
		);

		$changeListTransformer = new ChangeRunCoalescer(
			$this->getStore()->getEntityRevisionLookup(),
			$this->getEntityChangeFactory(),
			$this->settings->getSetting( 'siteGlobalID' )
		);

		return new ChangeHandler(
			$this->getAffectedPagesFinder(),
			new TitleFactory(),
			$pageUpdater,
			$changeListTransformer,
			$this->siteLookup,
			$this->settings->getSetting( 'injectRecentChanges' )
		);
	}

	/**
	 * @return RecentChangeFactory
	 */
	private function getRecentChangeFactory() {
		return new RecentChangeFactory(
			$this->getContentLanguage(),
			new SiteLinkCommentCreator(
				$this->getContentLanguage(),
				$this->siteLookup,
				$this->settings->getSetting( 'siteGlobalID' )
			)
		);
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 *
	 * @return MediaWikiContentLanguages
	 */
	public function getTermsLanguages() {
		return new MediaWikiContentLanguages();
	}

	/**
	 * @return RestrictedEntityLookup
	 */
	public function getRestrictedEntityLookup() {
		if ( $this->restrictedEntityLookup === null ) {
			$this->restrictedEntityLookup = new RestrictedEntityLookup(
				$this->getEntityLookup(),
				$this->settings->getSetting( 'entityAccessLimit' )
			);
		}

		return $this->restrictedEntityLookup;
	}

	/**
	 * @return PropertyOrderProvider
	 */
	public function getPropertyOrderProvider() {
		if ( $this->propertyOrderProvider === null ) {
			$title = Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' );
			$innerProvider = new WikiPagePropertyOrderProvider( $title );

			$url = $this->settings->getSetting( 'propertyOrderUrl' );
			if ( $url !== null ) {
				$innerProvider = new FallbackPropertyOrderProvider(
					$innerProvider,
					new HttpUrlPropertyOrderProvider( $url, new Http() )
				);
			}

			$this->propertyOrderProvider = new CachingPropertyOrderProvider(
				$innerProvider,
				wfGetMainCache()
			);
		}

		return $this->propertyOrderProvider;
	}

	/**
	 * @return int[] An array mapping entity type identifiers to namespace numbers.
	 */
	private function buildEntityNamespaceConfigurations() {
		$namespaces = $this->settings->getSetting( 'entityNamespaces' );
		Hooks::run( 'WikibaseEntityNamespaces', array( &$namespaces ) );
		return $namespaces;
	}

	/**
	 * @return EntityNamespaceLookup
	 */
	public function getEntityNamespaceLookup() {
		if ( $this->entityNamespaceLookup === null ) {
			$this->entityNamespaceLookup = new EntityNamespaceLookup(
				$this->buildEntityNamespaceConfigurations()
			);
		}

		return $this->entityNamespaceLookup;
	}

	/**
	 * @param Language $language
	 *
	 * @return LanguageFallbackChain
	 */
	public function getDataAccessLanguageFallbackChain( Language $language ) {
		return $this->getLanguageFallbackChainFactory()->newFromLanguage(
			$language,
			LanguageFallbackChainFactory::FALLBACK_ALL
		);
	}

	/**
	 * @return RepositoryDefinitions
	 */
	public function getRepositoryDefinitions() {
		return $this->repositoryDefinitions;
	}

}
