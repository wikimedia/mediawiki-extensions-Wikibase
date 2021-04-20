<?php

namespace Wikibase\Repo;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Exception;
use HashBagOStuff;
use IContextSource;
use Language;
use LogicException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Site\MediaWikiPageNameNormalizer;
use MWException;
use ObjectCache;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RequestContext;
use Serializers\Serializer;
use SiteLookup;
use SpecialPage;
use Title;
use User;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataAccess\AliasTermBuffer;
use Wikibase\DataAccess\DataAccessSettings;
use Wikibase\DataAccess\EntitySource;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataAccess\PrefetchingTermLookup;
use Wikibase\DataAccess\PrefetchingTermLookupFactory;
use Wikibase\DataAccess\SingleEntitySourceServicesFactory;
use Wikibase\DataAccess\WikibaseServices;
use Wikibase\DataModel\DeserializerFactory;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\LegacyAdapterPropertyLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
use Wikibase\DataModel\Services\Statement\GuidGenerator;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Services\Term\TermBuffer;
use Wikibase\InternalSerialization\DeserializerFactory as InternalDeserializerFactory;
use Wikibase\Lib\Changes\EntityChangeFactory;
use Wikibase\Lib\ContentLanguages;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\DataValueFactory;
use Wikibase\Lib\EntityFactory;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\CachingKartographerEmbeddingHandler;
use Wikibase\Lib\Formatters\EntityIdPlainLinkFormatter;
use Wikibase\Lib\Formatters\EntityIdValueFormatter;
use Wikibase\Lib\Formatters\FormatterLabelDescriptionLookupFactory;
use Wikibase\Lib\Formatters\MediaWikiNumberLocalizer;
use Wikibase\Lib\Formatters\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\Formatters\OutputFormatValueFormatterFactory;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Lib\Formatters\WikibaseSnakFormatterBuilders;
use Wikibase\Lib\Formatters\WikibaseValueFormatterBuilders;
use Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor;
use Wikibase\Lib\LanguageFallbackChainFactory;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Modules\PropertyValueExpertsModule;
use Wikibase\Lib\Modules\SettingsValueProvider;
use Wikibase\Lib\PropertyInfoDataTypeLookup;
use Wikibase\Lib\SettingsArray;
use Wikibase\Lib\Store\CachingPropertyOrderProvider;
use Wikibase\Lib\Store\EntityArticleIdLookup;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\EntityExistenceChecker;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Lib\Store\EntityRedirectChecker;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\EntityStore;
use Wikibase\Lib\Store\EntityStoreWatcher;
use Wikibase\Lib\Store\EntityTermStoreWriter;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\EntityTitleTextLookup;
use Wikibase\Lib\Store\EntityUrlLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\LinkTargetEntityIdLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Lib\Store\PropertyInfoStore;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\Store\WikiPagePropertyOrderProvider;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\TermChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\EntityChangeOpProvider;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\ItemHandler;
use Wikibase\Repo\Content\PropertyHandler;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\DifferencesSnakVisualizer;
use Wikibase\Repo\Diff\EntityDiffVisualizerFactory;
use Wikibase\Repo\EditEntity\EditFilterHookRunner;
use Wikibase\Repo\EditEntity\MediawikiEditEntityFactory;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorDelegator;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\FederatedProperties\WrappingEntityIdFormatterFactory;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Localizer\ChangeOpApplyExceptionLocalizer;
use Wikibase\Repo\Localizer\ChangeOpDeserializationExceptionLocalizer;
use Wikibase\Repo\Localizer\ChangeOpValidationExceptionLocalizer;
use Wikibase\Repo\Localizer\DispatchingExceptionLocalizer;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Localizer\GenericExceptionLocalizer;
use Wikibase\Repo\Localizer\MessageExceptionLocalizer;
use Wikibase\Repo\Localizer\ParseExceptionLocalizer;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGenerator;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Search\Fields\FieldDefinitions;
use Wikibase\Repo\Search\Fields\NoFieldDefinitions;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\Repo\View\WikibaseHtmlSnakFormatterFactory;
use Wikibase\View\EntityIdFormatterFactory;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\ViewFactory;

/**
 * Top level factory for the WikibaseRepo extension.
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Daniel Kinzler
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class WikibaseRepo {

	/**
	 * @var SnakFactory|null
	 */
	private $snakFactory = null;

	/**
	 * @var PropertyDataTypeLookup|null
	 */
	private $propertyDataTypeLookup = null;

	/**
	 * @var OutputFormatSnakFormatterFactory|null
	 */
	private $snakFormatterFactory = null;

	/**
	 * @var SummaryFormatter|null
	 */
	private $summaryFormatter = null;

	/**
	 * @var ExceptionLocalizer|null
	 */
	private $exceptionLocalizer = null;

	/**
	 * @var CachingCommonsMediaFileNameLookup|null
	 */
	private $cachingCommonsMediaFileNameLookup = null;

	/**
	 * @var WikibaseRepo|null
	 */
	private static $instance = null;

	/**
	 * @var ValidatorBuilders|null
	 */
	private static $validatorBuilders = null;

	/**
	 * @var WikibaseValueFormatterBuilders|null
	 */
	private static $valueFormatterBuilders = null;

	/**
	 * @var WikibaseSnakFormatterBuilders|null
	 */
	private static $snakFormatterBuilders = null;

	public static function resetClassStatics() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new Exception(
				'Cannot reset WikibaseRepo class statics outside of tests.'
			);
		}
		self::$instance = null;
		self::$validatorBuilders = null;
		self::$valueFormatterBuilders = null;
		self::$snakFormatterBuilders = null;
		ApiServiceFactory::resetClassStatics();
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @throws MWException
	 * @return self
	 */
	private static function newInstance() {
		return new self();
	}

	/**
	 * IMPORTANT: Use only when it is not feasible to inject an instance properly.
	 *
	 * @return self
	 */
	public static function getDefaultInstance() {
		if ( self::$instance === null ) {
			self::$instance = self::newInstance();
		}

		return self::$instance;
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getDataTypeValidatorFactory() instead!
	 *
	 * @return ValidatorBuilders
	 */
	public static function getDefaultValidatorBuilders() {
		if ( self::$validatorBuilders === null ) {
			$wikibaseRepo = self::getDefaultInstance();
			self::$validatorBuilders = $wikibaseRepo->newValidatorBuilders();
		}

		return self::$validatorBuilders;
	}

	/**
	 * Returns a low level factory object for creating validators for well known data types.
	 *
	 * @warning This is for use with getDefaultValidatorBuilders() during bootstrap only!
	 * Program logic should use WikibaseRepo::getDataTypeValidatorFactory() instead!
	 *
	 * @return ValidatorBuilders
	 */
	public function newValidatorBuilders() {
		$urlSchemes = self::getSettings()->getSetting( 'urlSchemes' );

		return new ValidatorBuilders(
			self::getEntityLookup(),
			self::getEntityIdParser(),
			$urlSchemes,
			self::getItemVocabularyBaseUri(),
			self::getMonolingualTextLanguages(),
			$this->getCachingCommonsMediaFileNameLookup(),
			new MediaWikiPageNameNormalizer(),
			self::getSettings()->getSetting( 'geoShapeStorageApiEndpointUrl' ),
			self::getSettings()->getSetting( 'tabularDataStorageApiEndpointUrl' )
		);
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	public static function getDefaultValueFormatterBuilders() {
		if ( self::$valueFormatterBuilders === null ) {
			global $wgThumbLimits;
			$wikibaseRepo = self::getDefaultInstance();
			self::$valueFormatterBuilders = $wikibaseRepo->newWikibaseValueFormatterBuilders( $wgThumbLimits );
		}

		return self::$valueFormatterBuilders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @param array $thumbLimits
	 *
	 * @return WikibaseValueFormatterBuilders
	 */
	private function newWikibaseValueFormatterBuilders( array $thumbLimits ) {
		return new WikibaseValueFormatterBuilders(
			new FormatterLabelDescriptionLookupFactory( self::getTermLookup() ),
			$this->getLanguageNameLookup(),
			self::getItemUrlParser(),
			self::getSettings()->getSetting( 'geoShapeStorageBaseUrl' ),
			self::getSettings()->getSetting( 'tabularDataStorageBaseUrl' ),
			self::getTermFallbackCache(),
			self::getSettings()->getSetting( 'sharedCacheDuration' ),
			self::getEntityLookup(),
			$this->getEntityRevisionLookup(),
			self::getSettings()->getSetting( 'entitySchemaNamespace' ),
			self::getEntityExistenceChecker(),
			self::getEntityTitleTextLookup(),
			self::getEntityUrlLookup(),
			self::getEntityRedirectChecker(),
			self::getEntityTitleLookup(),
			self::getKartographerEmbeddingHandler(),
			self::getSettings()->getSetting( 'useKartographerMaplinkInWikitext' ),
			$thumbLimits
		);
	}

	public static function getKartographerEmbeddingHandler( ContainerInterface $services = null ): ?CachingKartographerEmbeddingHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.KartographerEmbeddingHandler' );
	}

	/**
	 * @return LanguageNameLookup
	 */
	public function getLanguageNameLookup() {
		return new LanguageNameLookup( self::getUserLanguage()->getCode() );
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	public static function getDefaultSnakFormatterBuilders() {
		if ( self::$snakFormatterBuilders === null ) {
			self::$snakFormatterBuilders = self::getDefaultInstance()->newWikibaseSnakFormatterBuilders(
				self::getDefaultValueFormatterBuilders()
			);
		}

		return self::$snakFormatterBuilders;
	}

	/**
	 * Returns a low level factory object for creating formatters for well known data types.
	 *
	 * @warning This is for use with getDefaultValueFormatterBuilders() during bootstrap only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 *
	 * @param WikibaseValueFormatterBuilders $valueFormatterBuilders
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	private function newWikibaseSnakFormatterBuilders( WikibaseValueFormatterBuilders $valueFormatterBuilders ) {
		return new WikibaseSnakFormatterBuilders(
			$valueFormatterBuilders,
			self::getStore()->getPropertyInfoLookup(),
			$this->getPropertyDataTypeLookup(),
			self::getDataTypeFactory()
		);
	}

	public function __construct() {
		// TODO remove
	}

	/**
	 * @throws MWException when called to early
	 * @return Language
	 */
	private function getContentLanguage() {
		/**
		 * Before this constant is defined, custom config may not have been taken into account.
		 * So try not to allow code to use a language before that point.
		 * This code was explicitly mentioning the SetupAfterCache hook.
		 * With services, that hook won't be a problem anymore.
		 * So this check may well be unnecessary (but better safe than sorry).
		 */
		if ( !defined( 'MW_SERVICE_BOOTSTRAP_COMPLETE' ) ) {
			throw new MWException( 'Premature access to MediaWiki ContentLanguage!' );
		}

		return MediaWikiServices::getInstance()->getContentLanguage();
	}

	/**
	 * @deprecated
	 * @throws MWException when called too early
	 */
	public static function getUserLanguage( ContainerInterface $services = null ): Language {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.UserLanguage' );
	}

	public static function getDataTypeFactory( ContainerInterface $services = null ): DataTypeFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataTypeFactory' );
	}

	public static function getValueParserFactory( ContainerInterface $services = null ): ValueParserFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ValueParserFactory' );
	}

	public static function getDataValueFactory( ContainerInterface $services = null ): DataValueFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataValueFactory' );
	}

	public static function getEntityContentFactory( ContainerInterface $services = null ): EntityContentFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityContentFactory' );
	}

	public static function getEntityTypeDefinitions( ContainerInterface $services = null ): EntityTypeDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityTypeDefinitions' );
	}

	public static function getEntityChangeFactory( ContainerInterface $services = null ): EntityChangeFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityChangeFactory' );
	}

	public static function getEntityDiffer( ContainerInterface $services = null ): EntityDiffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityDiffer' );
	}

	public static function getEntityPatcher( ContainerInterface $services = null ): EntityPatcher {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityPatcher' );
	}

	public static function getEntityStoreWatcher( ContainerInterface $services = null ): EntityStoreWatcher {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityStoreWatcher' );
	}

	public static function getEntityTitleLookup( ContainerInterface $services = null ): EntityTitleLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityTitleLookup' );
	}

	public static function getEntityTitleStoreLookup( ContainerInterface $services = null ): EntityTitleStoreLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityTitleStoreLookup' );
	}

	public static function getEntityTitleTextLookup( ContainerInterface $services = null ): EntityTitleTextLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityTitleTextLookup' );
	}

	public static function getEntityUrlLookup( ContainerInterface $services = null ): EntityUrlLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityUrlLookup' );
	}

	public static function getEntityArticleIdLookup( ContainerInterface $services = null ): EntityArticleIdLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityArticleIdLookup' );
	}

	public static function getEntityExistenceChecker( ContainerInterface $services = null ): EntityExistenceChecker {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityExistenceChecker' );
	}

	public static function getEntityRedirectChecker( ContainerInterface $services = null ): EntityRedirectChecker {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityRedirectChecker' );
	}

	public static function getEntityIdLookup( ContainerInterface $services = null ): EntityIdLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityIdLookup' );
	}

	public static function getLocalRepoWikiPageMetaDataAccessor( ContainerInterface $services = null ): WikiPageEntityMetaDataAccessor {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalRepoWikiPageMetaDataAccessor' );
	}

	/**
	 * @see Store::getEntityRevisionLookup
	 *
	 * @param string $cache One of Store::LOOKUP_CACHING_*
	 *        Store::LOOKUP_CACHING_DISABLED to get an uncached direct lookup
	 *        Store::LOOKUP_CACHING_RETRIEVE_ONLY to get a lookup which reads from the cache, but doesn't store retrieved entities
	 *        Store::LOOKUP_CACHING_ENABLED to get a caching lookup (default)
	 *
	 * @return EntityRevisionLookup
	 */
	public function getEntityRevisionLookup( $cache = Store::LOOKUP_CACHING_ENABLED ) {
		return self::getStore()->getEntityRevisionLookup( $cache );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityRevisionLookupFactoryCallbacks() {
		return self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_REVISION_LOOKUP_FACTORY_CALLBACK );
	}

	/**
	 * @param User $user
	 * @param IContextSource $context
	 *
	 * @return ItemRedirectCreationInteractor
	 */
	public function newItemRedirectCreationInteractor( User $user, IContextSource $context ) {
		return new ItemRedirectCreationInteractor(
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEntityStore(),
			self::getEntityPermissionChecker(),
			$this->getSummaryFormatter(),
			$user,
			$this->newEditFilterHookRunner( $context ),
			self::getStore()->getEntityRedirectLookup(),
			self::getEntityTitleStoreLookup()
		);
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return EditFilterHookRunner
	 */
	private function newEditFilterHookRunner( IContextSource $context ) {
		return new MediawikiEditFilterHookRunner(
			self::getEntityNamespaceLookup(),
			self::getEntityTitleStoreLookup(),
			self::getEntityContentFactory(),
			$context
		);
	}

	/**
	 * @param string $displayLanguageCode
	 *
	 * @return MatchingTermsLookupSearchInteractor
	 */
	public function newTermSearchInteractor( $displayLanguageCode ) {
		return self::getWikibaseServices()->getTermSearchInteractorFactory()->newInteractor(
			$displayLanguageCode
		);
	}

	public static function getEntityStore( ContainerInterface $services = null ): EntityStore {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityStore' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntityStoreFactoryCallbacks() {
		return self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_STORE_FACTORY_CALLBACK );
	}

	public function getPropertyDataTypeLookup(): PropertyDataTypeLookup {
		if ( $this->propertyDataTypeLookup === null ) {
			$this->propertyDataTypeLookup = $this->newPropertyDataTypeLookup();
		}

		return $this->propertyDataTypeLookup;
	}

	public function newPropertyDataTypeLookup(): PropertyDataTypeLookup {
		if ( $this->inFederatedPropertyMode() ) {
			return $this->newFederatedPropertiesServiceFactory()->newApiPropertyDataTypeLookup();
		}

		return $this->newPropertyDataTypeLookupForLocalProperties();
	}

	private function newPropertyDataTypeLookupForLocalProperties(): PropertyDataTypeLookup {
		$infoLookup = self::getStore()->getPropertyInfoLookup();
		$retrievingLookup = new EntityRetrievingDataTypeLookup( self::getEntityLookup() );
		return new PropertyInfoDataTypeLookup(
			$infoLookup,
			self::getLogger(),
			$retrievingLookup
		);
	}

	public static function getStringNormalizer( ContainerInterface $services = null ): StringNormalizer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.StringNormalizer' );
	}

	/**
	 * Get a caching entity lookup that reads from a replica DB.
	 *
	 * If you need different caching or lookup modes, use {@link Store::getEntityLookup()} instead.
	 */
	public static function getEntityLookup( ContainerInterface $services = null ): EntityLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityLookup' );
	}

	public function getPropertyLookup( $cacheMode = Store::LOOKUP_CACHING_ENABLED ): PropertyLookup {
		return new LegacyAdapterPropertyLookup( self::getStore()->getEntityLookup( $cacheMode ) );
	}

	/**
	 * @return SnakFactory
	 */
	public function getSnakFactory() {
		if ( $this->snakFactory === null ) {
			$this->snakFactory = new SnakFactory(
				$this->getPropertyDataTypeLookup(),
				self::getDataTypeFactory(),
				self::getDataValueFactory()
			);
		}

		return $this->snakFactory;
	}

	public static function getEntityIdParser( ContainerInterface $services = null ): EntityIdParser {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityIdParser' );
	}

	public static function getEntityIdComposer( ContainerInterface $services = null ): EntityIdComposer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityIdComposer' );
	}

	public static function getStatementGuidParser( ContainerInterface $services = null ): StatementGuidParser {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.StatementGuidParser' );
	}

	/**
	 * @return ChangeOpFactoryProvider
	 */
	public function getChangeOpFactoryProvider() {
		$snakValidator = new SnakValidator(
			$this->getPropertyDataTypeLookup(),
			self::getDataTypeFactory(),
			self::getDataTypeValidatorFactory()
		);

		return new ChangeOpFactoryProvider(
			self::getEntityConstraintProvider(),
			new GuidGenerator(),
			self::getStatementGuidValidator(),
			self::getStatementGuidParser(),
			$snakValidator,
			self::getTermValidatorFactory(),
			$this->getSiteLookup(),
			array_keys( self::getSettings()->getSetting( 'badgeItems' ) )
		);
	}

	public static function getSiteLinkBadgeChangeOpSerializationValidator(
		ContainerInterface $services = null
	): SiteLinkBadgeChangeOpSerializationValidator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.SiteLinkBadgeChangeOpSerializationValidator' );
	}

	public static function getEntityChangeOpProvider( ContainerInterface $services = null ): EntityChangeOpProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityChangeOpProvider' );
	}

	/**
	 * TODO: this should be probably cached?
	 *
	 * @return ChangeOpDeserializerFactory
	 */
	public function getChangeOpDeserializerFactory() {
		$changeOpFactoryProvider = $this->getChangeOpFactoryProvider();

		return new ChangeOpDeserializerFactory(
			$changeOpFactoryProvider->getFingerprintChangeOpFactory(),
			$changeOpFactoryProvider->getStatementChangeOpFactory(),
			$changeOpFactoryProvider->getSiteLinkChangeOpFactory(),
			new TermChangeOpSerializationValidator( self::getTermsLanguages() ),
			self::getSiteLinkBadgeChangeOpSerializationValidator(),
			self::getExternalFormatStatementDeserializer(),
			new SiteLinkTargetProvider(
				$this->getSiteLookup(),
				self::getSettings()->getSetting( 'specialSiteLinkGroups' )
			),
			self::getEntityIdParser(),
			self::getStringNormalizer(),
			self::getSettings()->getSetting( 'siteLinkGroups' )
		);
	}

	public static function getLanguageFallbackChainFactory( ContainerInterface $services = null ): LanguageFallbackChainFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LanguageFallbackChainFactory' );
	}

	public static function getLanguageFallbackLabelDescriptionLookupFactory(
		ContainerInterface $services = null
	): LanguageFallbackLabelDescriptionLookupFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LanguageFallbackLabelDescriptionLookupFactory' );
	}

	public static function getStatementGuidValidator( ContainerInterface $service = null ): StatementGuidValidator {
		return ( $service ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.StatementGuidValidator' );
	}

	public static function getSettings( ContainerInterface $services = null ): SettingsArray {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.Settings' );
	}

	public static function getIdGenerator( ContainerInterface $services = null ): IdGenerator {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.IdGenerator' );
	}

	public static function getStore( ContainerInterface $services = null ): Store {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.Store' );
	}

	public static function getLocalEntitySource( ContainerInterface $services = null ): EntitySource {
		// EntitySource bearing the same name as the localEntitySourceName setting
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalEntitySource' );
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
				self::getDataTypeDefinitions()->getSnakFormatterFactoryCallbacks(),
				self::getValueFormatterFactory(),
				$this->getPropertyDataTypeLookup(),
				self::getDataTypeFactory()
			);
		}

		return $this->snakFormatterFactory;
	}

	public static function getTermBuffer( ContainerInterface $services = null ): TermBuffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermBuffer' );
	}

	public static function getAliasTermBuffer( ContainerInterface $services = null ): AliasTermBuffer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.AliasTermBuffer' );
	}

	public static function getTermLookup( ContainerInterface $services = null ): TermLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermLookup' );
	}

	public static function getPrefetchingTermLookupFactory(
		ContainerInterface $services = null
	): PrefetchingTermLookupFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PrefetchingTermLookupFactory' );
	}

	public static function getPrefetchingTermLookup( ContainerInterface $services = null ): PrefetchingTermLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PrefetchingTermLookup' );
	}

	public static function getItemUrlParser( ContainerInterface $services = null ): SuffixEntityIdParser {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ItemUrlParser' );
	}

	public static function getItemVocabularyBaseUri( ContainerInterface $services = null ): string {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ItemVocabularyBaseUri' );
	}

	public static function getValueFormatterFactory( ContainerInterface $services = null ): OutputFormatValueFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ValueFormatterFactory' );
	}

	public static function getValueSnakRdfBuilderFactory( ContainerInterface $services = null ): ValueSnakRdfBuilderFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ValueSnakRdfBuilderFactory' );
	}

	public static function getRdfVocabulary( ContainerInterface $services = null ): RdfVocabulary {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.RdfVocabulary' );
	}

	/**
	 * @return ExceptionLocalizer
	 */
	public function getExceptionLocalizer() {
		if ( $this->exceptionLocalizer === null ) {
			$formatter = self::getMessageParameterFormatter();
			$localizers = $this->getExceptionLocalizers( $formatter );

			$this->exceptionLocalizer = new DispatchingExceptionLocalizer( $localizers );
		}

		return $this->exceptionLocalizer;
	}

	/**
	 * @param ValueFormatter $formatter
	 *
	 * @return ExceptionLocalizer[]
	 */
	private function getExceptionLocalizers( ValueFormatter $formatter ) {
		return [
			'MessageException' => new MessageExceptionLocalizer(),
			'ParseException' => new ParseExceptionLocalizer(),
			'ChangeOpValidationException' => new ChangeOpValidationExceptionLocalizer( $formatter ),
			'ChangeOpDeserializationException' => new ChangeOpDeserializationExceptionLocalizer(),
			'ChangeOpApplyException' => new ChangeOpApplyExceptionLocalizer(),
			'Exception' => new GenericExceptionLocalizer()
		];
	}

	/**
	 * @return SummaryFormatter
	 */
	public function getSummaryFormatter() {
		if ( $this->summaryFormatter === null ) {
			$this->summaryFormatter = $this->newSummaryFormatter();
		}

		return $this->summaryFormatter;
	}

	/**
	 * @return SummaryFormatter
	 */
	private function newSummaryFormatter() {
		// This needs to use an EntityIdPlainLinkFormatter as we want to mangle
		// the links created in HtmlPageLinkRendererEndHookHandler afterwards (the links must not
		// contain a display text: [[Item:Q1]] is fine but [[Item:Q1|Q1]] isn't).
		$idFormatter = new EntityIdPlainLinkFormatter( self::getEntityTitleLookup() );

		$formatterFactoryCBs = self::getDataTypeDefinitions()
			->getFormatterFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE );

		// Iterate through all defined entity types and override the formatter for entity IDs.
		foreach ( self::getEntityTypeDefinitions()->getEntityTypes() as $entityType ) {
			$formatterFactoryCBs[ "PT:wikibase-$entityType" ] = function (
				$format,
				FormatterOptions $options ) use ( $idFormatter ) {
				if ( $format === SnakFormatter::FORMAT_PLAIN ) {
					return new EntityIdValueFormatter( $idFormatter );
				} else {
					return null;
				}
			};
		}

		// Create a new ValueFormatterFactory from entity definition overrides.
		$valueFormatterFactory = new OutputFormatValueFormatterFactory(
			$formatterFactoryCBs,
			$this->getContentLanguage(),
			self::getLanguageFallbackChainFactory()
		);

		// Create a new SnakFormatterFactory based on the specialized ValueFormatterFactory.
		$snakFormatterFactory = new OutputFormatSnakFormatterFactory(
			[], // XXX: do we want $this->dataTypeDefinitions->getSnakFormatterFactoryCallbacks()
			$valueFormatterFactory,
			$this->getPropertyDataTypeLookup(),
			self::getDataTypeFactory()
		);

		$options = new FormatterOptions();
		$snakFormatter = $snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$options
		);
		$valueFormatter = $valueFormatterFactory->getValueFormatter(
			SnakFormatter::FORMAT_PLAIN,
			$options
		);

		$formatter = new SummaryFormatter(
			$idFormatter,
			$valueFormatter,
			$snakFormatter,
			$this->getContentLanguage(),
			self::getEntityIdParser()
		);

		return $formatter;
	}

	public static function getEntityPermissionChecker( ContainerInterface $services = null ): EntityPermissionChecker {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityPermissionChecker' );
	}

	public static function getTermValidatorFactory( ContainerInterface $services = null ): TermValidatorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermValidatorFactory' );
	}

	public static function getTermsCollisionDetectorFactory( ContainerInterface $services = null ): TermsCollisionDetectorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermsCollisionDetectorFactory' );
	}

	public static function getPropertyTermsCollisionDetector( ContainerInterface $services = null ): TermsCollisionDetector {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyTermsCollisionDetector' );
	}

	public static function getItemTermsCollisionDetector( ContainerInterface $services = null ): TermsCollisionDetector {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ItemTermsCollisionDetector' );
	}

	public static function getEntityConstraintProvider( ContainerInterface $services = null ): EntityConstraintProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityConstraintProvider' );
	}

	/**
	 * @return ValidatorErrorLocalizer
	 */
	public function getValidatorErrorLocalizer() {
		return new ValidatorErrorLocalizer( self::getMessageParameterFormatter() );
	}

	/**
	 * @return SiteLookup
	 */
	public function getSiteLookup() {
		return MediaWikiServices::getInstance()->getSiteLookup();
	}

	/**
	 * Returns a ValueFormatter suitable for converting message parameters to wikitext.
	 * The formatter is most likely implemented to dispatch to different formatters internally,
	 * based on the type of the parameter.
	 */
	public static function getMessageParameterFormatter( ContainerInterface $services = null ): ValueFormatter {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.MessageParameterFormatter' );
	}

	public static function getChangeNotifier( ContainerInterface $services = null ): ChangeNotifier {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ChangeNotifier' );
	}

	/**
	 * Get the mapping of entity types => content models
	 */
	public static function getContentModelMappings( ContainerInterface $services = null ): array {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ContentModelMappings' );
	}

	public static function getEntityFactory( ContainerInterface $services = null ): EntityFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityFactory' );
	}

	/**
	 * @return string[] List of entity type identifiers (typically "item" and "property")
	 *  that are configured in WikibaseRepo.entitytypes.php and enabled via the
	 *  $wgWBRepoSettings['entityNamespaces'] setting.
	 *  This list will also include any sub entity types of entity types defined in $wgWBRepoSettings['entityNamespaces'].
	 *  Optionally the list also contains entity types from the configured foreign repositories.
	 */
	public function getEnabledEntityTypes() {
		$types = array_keys( self::getEntitySourceDefinitions()->getEntityTypeToSourceMapping() );
		$subEntityTypes = self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::SUB_ENTITY_TYPES );

		return array_reduce(
			$types,
			function ( $carry, $x ) use ( $subEntityTypes ) {
				$carry[] = $x;
				if ( array_key_exists( $x, $subEntityTypes ) ) {
					$carry = array_merge( $carry, $subEntityTypes[$x] );
				}
				return $carry;
			},
			[]
		);
	}

	public static function getLocalEntityTypes( ContainerInterface $services = null ): array {
		// List of entity and sub-entity type identifiers (typically "item" and "property")
		// that are configured in WikibaseRepo.entitytypes.php and enabled via the
		// $wgWBRepoSettings['entityNamespaces'] setting.
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalEntityTypes' );
	}

	/**
	 * @return EntityContentDataCodec
	 */
	public function getEntityContentDataCodec() {
		return new EntityContentDataCodec(
			self::getEntityIdParser(),
			self::getStorageEntitySerializer(),
			self::getInternalFormatEntityDeserializer(),
			self::getDataAccessSettings()->maxSerializedEntitySizeInBytes()
		);
	}

	public static function getBaseDataModelDeserializerFactory( ContainerInterface $services = null ): DeserializerFactory {
		// Returns a factory with knowledge about items, properties, and the
		// elements they are made of, but no other entity types.
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.BaseDataModelDeserializerFactory' );
	}

	public static function getInternalFormatDeserializerFactory( ContainerInterface $services = null ): InternalDeserializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.InternalFormatDeserializerFactory' );
	}

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the full
	 *  (expanded) serialization.
	 */
	public static function getBaseDataModelSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.BaseDataModelSerializerFactory' );
	}

	/**
	 * @return SerializerFactory A factory with knowledge about items, properties, and the elements
	 *  they are made of, but no other entity types. Returns serializers that generate the most
	 *  compact serialization.
	 */
	public static function getCompactBaseDataModelSerializerFactory( ContainerInterface $services = null ): SerializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.CompactBaseDataModelSerializerFactory' );
	}

	public static function getAllTypesEntityDeserializer(
		ContainerInterface $services = null
	): DispatchableDeserializer {
		// Returns a deserializer to deserialize entities in current serialization only.
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.AllTypesEntityDeserializer' );
	}

	/**
	 * Returns a deserializer to deserialize entities in both current and legacy serialization.
	 */
	public static function getInternalFormatEntityDeserializer( ContainerInterface $services = null ): Deserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.InternalFormatEntityDeserializer' );
	}

	/**
	 * Entity serializer that generates the full (expanded) serialization.
	 */
	public static function getAllTypesEntitySerializer( ContainerInterface $services = null ): Serializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.AllTypesEntitySerializer' );
	}

	/**
	 * Entity serializer that generates the most compact serialization.
	 */
	public static function getCompactEntitySerializer( ContainerInterface $services = null ): Serializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.CompactEntitySerializer' );
	}

	/**
	 * Returns the entity serializer that generates serialization that is used in the storage layer.
	 */
	public static function getStorageEntitySerializer( ContainerInterface $services = null ): Serializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.StorageEntitySerializer' );
	}

	/**
	 * Returns a deserializer to deserialize statements in current serialization only.
	 */
	public static function getExternalFormatStatementDeserializer( ContainerInterface $services = null ): Deserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ExternalFormatStatementDeserializer' );
	}

	/**
	 * @return DataValueDeserializer
	 */
	public static function getDataValueDeserializer( ContainerInterface $services = null ): DataValueDeserializer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataValueDeserializer' );
	}

	public function newItemHandler(): ItemHandler {
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = self::getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$siteLinkStore = self::getStore()->newSiteLinkStore();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		return new ItemHandler(
			self::getItemTermStoreWriter(),
			$codec,
			$constraintProvider,
			$errorLocalizer,
			self::getEntityIdParser(),
			$siteLinkStore,
			self::getEntityIdLookup(),
			self::getLanguageFallbackLabelDescriptionLookupFactory(),
			$this->getFieldDefinitionsByType( Item::ENTITY_TYPE ),
			$this->getPropertyDataTypeLookup(),
			$legacyFormatDetector
		);
	}

	public static function getPropertyTermStoreWriter( ContainerInterface $services = null ): EntityTermStoreWriter {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyTermStoreWriter' );
	}

	public static function getItemTermStoreWriter( ContainerInterface $services = null ): EntityTermStoreWriter {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ItemTermStoreWriter' );
	}

	public static function getTermStoreWriterFactory( ContainerInterface $services = null ): TermStoreWriterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermStoreWriterFactory' );
	}

	/**
	 * Do not use this service directly. Instead, use the service(s) for the interface(s) you need:
	 * * {@link getTypeIdsAcquirer} for {@link TypeIdsAcquirer}
	 * * {@link getTypeIdsLookup} for {@link TypeIdsLookup}
	 * * {@link getTypeIdsResolver} for {@link TypeIdsResolver}
	 */
	public static function getDatabaseTypeIdsStore( ContainerInterface $services = null ): DatabaseTypeIdsStore {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DatabaseTypeIdsStore' );
	}

	public static function getTypeIdsAcquirer( ContainerInterface $services = null ): TypeIdsAcquirer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TypeIdsAcquirer' );
	}

	public static function getTypeIdsLookup( ContainerInterface $services = null ): TypeIdsLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TypeIdsLookup' );
	}

	public static function getTypeIdsResolver( ContainerInterface $services = null ): TypeIdsResolver {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TypeIdsResolver' );
	}

	/**
	 * Get field definitions for entity depending on its type.
	 * @param string $type Entity type
	 * @return FieldDefinitions
	 */
	public function getFieldDefinitionsByType( $type ) {
		$definitions = self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::SEARCH_FIELD_DEFINITIONS );
		if ( isset( $definitions[$type] ) && is_callable( $definitions[$type] ) ) {
			return call_user_func( $definitions[$type], self::getTermsLanguages()->getLanguages(),
				self::getSettings() );
		}
		return new NoFieldDefinitions();
	}

	public function newPropertyHandler(): PropertyHandler {
		$codec = $this->getEntityContentDataCodec();
		$constraintProvider = self::getEntityConstraintProvider();
		$errorLocalizer = $this->getValidatorErrorLocalizer();
		$propertyInfoStore = self::getStore()->getPropertyInfoStore();
		$propertyInfoBuilder = $this->newPropertyInfoBuilder();
		$legacyFormatDetector = $this->getLegacyFormatDetectorCallback();

		return new PropertyHandler(
			self::getPropertyTermStoreWriter(),
			$codec,
			$constraintProvider,
			$errorLocalizer,
			self::getEntityIdParser(),
			self::getEntityIdLookup(),
			self::getLanguageFallbackLabelDescriptionLookupFactory(),
			$propertyInfoStore,
			$propertyInfoBuilder,
			$this->getFieldDefinitionsByType( Property::ENTITY_TYPE ),
			$legacyFormatDetector
		);
	}

	/**
	 * @return PropertyInfoBuilder
	 */
	public function newPropertyInfoBuilder() {
		$propertyIdMap = [];

		$formatterUrlProperty = self::getSettings()->getSetting( 'formatterUrlProperty' );
		if ( $formatterUrlProperty !== null ) {
			$propertyIdMap[PropertyInfoLookup::KEY_FORMATTER_URL] = new PropertyId(
				$formatterUrlProperty
			);
		}

		$canonicalUriProperty = self::getSettings()->getSetting( 'canonicalUriProperty' );
		if ( $canonicalUriProperty !== null ) {
			$propertyIdMap[PropertyInfoStore::KEY_CANONICAL_URI] = new PropertyId( $canonicalUriProperty );
		}

		return new PropertyInfoBuilder( $propertyIdMap );
	}

	private function getLegacyFormatDetectorCallback() {
		$transformOnExport = self::getSettings()->getSetting( 'transformLegacyFormatOnExport' );

		if ( !$transformOnExport ) {
			return null;
		}

		/**
		 * Detects blobs that may be using a legacy serialization format.
		 * WikibaseRepo uses this for the $legacyExportFormatDetector parameter
		 * when constructing EntityHandlers.
		 *
		 * @see WikibaseRepo::newItemHandler
		 * @see WikibaseRepo::newPropertyHandler
		 * @see EntityHandler::__construct
		 *
		 * @note: False positives (detecting a legacy format when really no legacy format was used)
		 * are acceptable, false negatives (failing to detect a legacy format when one was used)
		 * are not acceptable.
		 *
		 * @param string $blob
		 * @param string $format
		 *
		 * @return bool True if $blob seems to be using a legacy serialization format.
		 */
		return function ( $blob, $format ) {
			// The legacy serialization uses something like "entity":["item",21] or
			// even "entity":"p21" for the entity ID.
			return preg_match( '/"entity"\s*:/', $blob ) > 0;
		};
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ApiHelperFactory
	 */
	public function getApiHelperFactory( IContextSource $context ) {
		$services = MediaWikiServices::getInstance();

		return new ApiHelperFactory(
			self::getEntityTitleStoreLookup(),
			$this->getExceptionLocalizer(),
			$this->getPropertyDataTypeLookup(),
			$this->getSiteLookup(),
			$this->getSummaryFormatter(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			$this->newEditEntityFactory( $context ),
			self::getBaseDataModelSerializerFactory( $services ),
			self::getAllTypesEntitySerializer( $services ),
			self::getEntityIdParser( $services ),
			$services->getPermissionManager(),
			$services->getRevisionLookup(),
			$services->getTitleFactory(),
			self::getStore( $services )->getEntityByLinkedTitleLookup(),
			self::getEntityFactory(),
			self::getEntityStore()
		);
	}

	/**
	 * @param IContextSource|null $context
	 *
	 * @return MediawikiEditEntityFactory
	 */
	public function newEditEntityFactory( IContextSource $context = null ) {
		return new MediawikiEditEntityFactory(
			self::getEntityTitleStoreLookup(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEntityStore(),
			self::getEntityPermissionChecker(),
			self::getEntityDiffer(),
			self::getEntityPatcher(),
			$this->newEditFilterHookRunner( $context ?: RequestContext::getMain() ),
			MediaWikiServices::getInstance()->getStatsdDataFactory(),
			self::getSettings()->getSetting( 'maxSerializedEntitySize' )
		);
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ItemMergeInteractor
	 */
	public function newItemMergeInteractor( IContextSource $context ) {
		$user = $context->getUser();

		return new ItemMergeInteractor(
			$this->getChangeOpFactoryProvider()->getMergeFactory(),
			$this->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEntityStore(),
			self::getEntityPermissionChecker(),
			$this->getSummaryFormatter(),
			$user,
			$this->newItemRedirectCreationInteractor( $user, $context ),
			self::getEntityTitleStoreLookup(),
			MediaWikiServices::getInstance()->getPermissionManager()
		);
	}

	/**
	 * @return (int|string)[] An array mapping entity type identifiers to
	 * namespace numbers and optional slots.
	 */
	public function getLocalEntityNamespaces() {
		return self::getSettings()->getSetting( 'entityNamespaces' );
	}

	public static function getEntityNamespaceLookup( ContainerInterface $services = null ): EntityNamespaceLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityNamespaceLookup' );
	}

	public static function getLocalEntityNamespaceLookup( ContainerInterface $services = null ): EntityNamespaceLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalEntityNamespaceLookup' );
	}

	/**
	 * @return EntityIdFormatterFactory
	 */
	public function getEntityIdHtmlLinkFormatterFactory() {
		$factory = new EntityIdHtmlLinkFormatterFactory(
			self::getEntityTitleLookup(),
			$this->getLanguageNameLookup(),
			self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK )
		);
		if ( $this->inFederatedPropertyMode() ) {
			$factory = new WrappingEntityIdFormatterFactory( $factory );
		}
		return $factory;
	}

	public function getEntityViewFactory() {
		return new DispatchingEntityViewFactory( self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::VIEW_FACTORY_CALLBACK ) );
	}

	public function getEntityMetaTagsCreatorFactory() {
		return new DispatchingEntityMetaTagsCreatorFactory(
			self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::META_TAGS_CREATOR_CALLBACK )
		);
	}

	public function getEntityDataFormatProvider(): EntityDataFormatProvider {
		$entityDataFormatProvider = new EntityDataFormatProvider();
		$formats = self::getSettings()->getSetting( 'entityDataFormats' );
		$entityDataFormatProvider->setAllowedFormats( $formats );
		return $entityDataFormatProvider;
	}

	public function getEntityDataUriManager(): EntityDataUriManager {
		$entityDataFormatProvider = $this->getEntityDataFormatProvider();

		// build a mapping of formats to file extensions and include HTML
		$supportedExtensions = [];
		$supportedExtensions['html'] = 'html';
		foreach ( $entityDataFormatProvider->getSupportedFormats() as $format ) {
			$ext = $entityDataFormatProvider->getExtension( $format );

			if ( $ext !== null ) {
				$supportedExtensions[$format] = $ext;
			}
		}

		return new EntityDataUriManager(
			SpecialPage::getTitleFor( 'EntityData' ),
			$supportedExtensions,
			self::getSettings()->getSetting( 'entityDataCachePaths' ),
			self::getEntityTitleLookup()
		);
	}

	public function getEntityParserOutputGeneratorFactory(): EntityParserOutputGeneratorFactory {
		$services = MediaWikiServices::getInstance();

		return new EntityParserOutputGeneratorFactory(
			$this->getEntityViewFactory(),
			$this->getEntityMetaTagsCreatorFactory(),
			self::getEntityTitleLookup(),
			self::getLanguageFallbackChainFactory(),
			TemplateFactory::getDefaultInstance(),
			$this->getEntityDataFormatProvider(),
			// FIXME: Should this be done for all usages of this lookup, or is the impact of
			// CachingPropertyInfoLookup enough?
			new InProcessCachingDataTypeLookup( $this->getPropertyDataTypeLookup() ),
			self::getCompactEntitySerializer(),
			new EntityReferenceExtractorDelegator(
				self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_REFERENCE_EXTRACTOR_CALLBACK ),
				new StatementEntityReferenceExtractor( self::getItemUrlParser() )
			),
			self::getKartographerEmbeddingHandler(),
			$services->getStatsdDataFactory(),
			$services->getRepoGroup(),
			self::getSettings()->getSetting( 'preferredGeoDataProperties' ),
			self::getSettings()->getSetting( 'preferredPageImagesProperties' ),
			self::getSettings()->getSetting( 'globeUris' )
		);
	}

	public function getEntityParserOutputGenerator( Language $userLanguage ): EntityParserOutputGenerator {
		return $this->getEntityParserOutputGeneratorFactory()
			->getEntityParserOutputGenerator( $userLanguage );
	}

	/**
	 * @return ViewFactory
	 */
	public function getViewFactory() {
		$lang = self::getUserLanguage();

		$statementGrouperBuilder = new StatementGrouperBuilder(
			self::getSettings()->getSetting( 'statementSections' ),
			$this->getPropertyDataTypeLookup(),
			self::getStatementGuidParser()
		);

		$propertyOrderProvider = new CachingPropertyOrderProvider(
			new WikiPagePropertyOrderProvider(
				Title::newFromText( 'MediaWiki:Wikibase-SortedProperties' )
			),
			ObjectCache::getLocalClusterInstance()
		);

		return new ViewFactory(
			$this->getEntityIdHtmlLinkFormatterFactory(),
			new EntityIdLabelFormatterFactory(),
			new WikibaseHtmlSnakFormatterFactory( $this->getSnakFormatterFactory() ),
			$statementGrouperBuilder->getStatementGrouper(),
			$propertyOrderProvider,
			$this->getSiteLookup(),
			self::getDataTypeFactory(),
			TemplateFactory::getDefaultInstance(),
			$this->getLanguageNameLookup(),
			new MediaWikiLanguageDirectionalityLookup(),
			new MediaWikiNumberLocalizer( $lang ),
			self::getSettings()->getSetting( 'siteLinkGroups' ),
			self::getSettings()->getSetting( 'specialSiteLinkGroups' ),
			self::getSettings()->getSetting( 'badgeItems' ),
			new MediaWikiLocalizedTextProvider( $lang ),
			new RepoSpecialPageLinker()
		);
	}

	public static function getDataTypeValidatorFactory( ContainerInterface $services = null ): DataTypeValidatorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataTypeValidatorFactory' );
	}

	public static function getDataTypeDefinitions( ContainerInterface $services = null ): DataTypeDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataTypeDefinitions' );
	}

	public static function getWikibaseContentLanguages( ContainerInterface $services = null ): WikibaseContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.WikibaseContentLanguages' );
	}

	public static function getMonolingualTextLanguages( ContainerInterface $services = null ): ContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.MonolingualTextLanguages' );
	}

	/**
	 * Get a ContentLanguages object holding the languages available for labels, descriptions and aliases.
	 */
	public static function getTermsLanguages( ContainerInterface $services = null ): ContentLanguages {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermsLanguages' );
	}

	/**
	 * @return CachingCommonsMediaFileNameLookup
	 */
	private function getCachingCommonsMediaFileNameLookup() {
		if ( $this->cachingCommonsMediaFileNameLookup === null ) {
			$this->cachingCommonsMediaFileNameLookup = new CachingCommonsMediaFileNameLookup(
				new MediaWikiPageNameNormalizer(),
				new HashBagOStuff()
			);
		}

		return $this->cachingCommonsMediaFileNameLookup;
	}

	public function getEntityTypesConfigValue() {
		$entityTypeDefinitions = self::getEntityTypeDefinitions();
		return [
			'types' => $entityTypeDefinitions->getEntityTypes(),
			'deserializer-factory-functions'
				=> $entityTypeDefinitions->get( EntityTypeDefinitions::JS_DESERIALIZER_FACTORY_FUNCTION )
		];
	}

	public function getSettingsValueProvider( $jsSetting, $phpSetting ) {
		return new SettingsValueProvider( self::getSettings(), $jsSetting, $phpSetting );
	}

	public static function getUnitConverter( ContainerInterface $services = null ): ?UnitConverter {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.UnitConverter' );
	}

	public static function getEntityRdfBuilderFactory(
		ContainerInterface $services = null
	): EntityRdfBuilderFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityRdfBuilderFactory' );
	}

	/**
	 * @param IContextSource $contextSource
	 * @return EntityDiffVisualizerFactory
	 */
	public function getEntityDiffVisualizerFactory( IContextSource $contextSource ) {
		$langCode = $contextSource->getLanguage()->getCode();

		$options = new FormatterOptions( [
			//TODO: fallback chain
			ValueFormatter::OPT_LANG => $langCode
		] );

		$htmlFormatterFactory = $this->getEntityIdHtmlLinkFormatterFactory();
		$entityIdFormatter = $htmlFormatterFactory->getEntityIdFormatter( $contextSource->getLanguage() );

		$formatterFactory = $this->getSnakFormatterFactory();
		$detailedSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML_DIFF, $options );
		$terseSnakFormatter = $formatterFactory->getSnakFormatter( SnakFormatter::FORMAT_HTML, $options );

		return new EntityDiffVisualizerFactory(
			self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_DIFF_VISUALIZER_CALLBACK ),
			$contextSource,
			new ClaimDiffer( new OrderedListDiffer( new ComparableComparer() ) ),
			new ClaimDifferenceVisualizer(
				new DifferencesSnakVisualizer(
					$entityIdFormatter,
					$detailedSnakFormatter,
					$terseSnakFormatter,
					$langCode
				),
				$langCode
			),
			$this->getSiteLookup(),
			$entityIdFormatter
		);
	}

	/**
	 * @return string[][] Associative array mapping names of known entity types (strings) to lists of names of
	 *         repositories providing entities of those types.
	 *         Note: Currently entities of a given type are only provided by single source. This
	 *         assumption can be changed in the future.
	 */
	public function getEntityTypeToRepositoryMapping() {
		// Map all entity types to unprefixed repository.
		// TODO: This is a bit of a hack but does the job for EntityIdSearchHelper as long as there are no
		// prefixed IDs in the entity source realm. Probably EntityIdSearchHelper should be changed instead
		// of getting this map passed from Repo
		$entityTypes = array_keys( self::getEntitySourceDefinitions()->getEntityTypeToSourceMapping() );
		return array_fill_keys( $entityTypes, [ '' ] );
	}

	/**
	 * @return string[] Associative array mapping repository or entity source names to base URIs of concept URIs.
	 */
	public function getConceptBaseUris() {
		return self::getEntitySourceDefinitions()->getConceptBaseUris();
	}

	public static function getPropertyValueExpertsModule( ContainerInterface $services = null ): PropertyValueExpertsModule {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyValueExpertsModule' );
	}

	/**
	 * @deprecated
	 * DO NOT USE THIS SERVICE! This is just a temporary convenience placeholder until we finish migrating
	 * SingleEntitySourceServices. Will be removed with T277731
	 */
	public static function getSingleEntitySourceServicesFactory( ContainerInterface $services = null ): SingleEntitySourceServicesFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.SingleEntitySourceServicesFactory' );
	}

	public static function getWikibaseServices( ContainerInterface $services = null ): WikibaseServices {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.WikibaseServices' );
	}

	public static function getDataAccessSettings( ContainerInterface $services = null ): DataAccessSettings {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DataAccessSettings' );
	}

	public static function getEntitySourceDefinitions( ContainerInterface $services = null ): EntitySourceDefinitions {
		return ( $services ?: MediaWikiServices::getInstance() )->get( 'WikibaseRepo.EntitySourceDefinitions' );
	}

	/**
	 * @return callable[]
	 */
	public function getEntitySearchHelperCallbacks() {
		return self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::ENTITY_SEARCH_CALLBACK );
	}

	public function getEntityLinkFormatterFactory( Language $language ) {
		return new EntityLinkFormatterFactory(
			$language,
			self::getEntityTitleTextLookup(),
			self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::LINK_FORMATTER_CALLBACK )
		);
	}

	/**
	 * Get entity search helper callbacks.
	 * @return string[]
	 */
	public function getFulltextSearchTypes() {
		$searchTypes = self::getEntityTypeDefinitions()->get( EntityTypeDefinitions::FULLTEXT_SEARCH_CONTEXT );
		foreach ( $searchTypes as $key => $value ) {
			if ( is_callable( $value ) ) {
				$searchTypes[$key] = $value();
			}
		}
		return $searchTypes;
	}

	public static function getTermFallbackCache( ContainerInterface $services = null ): TermFallbackCacheFacade {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermFallbackCache' );
	}

	public static function getTermFallbackCacheFactory( ContainerInterface $services = null ): TermFallbackCacheFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.TermFallbackCacheFactory' );
	}

	public static function getLogger( ContainerInterface $services = null ): LoggerInterface {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.Logger' );
	}

	/**
	 * Guard against Federated properties services being constructed in wiring when feature is disabled.
	 */
	private function throwLogicExceptionIfFederatedPropertiesNotEnabledAndConfigured(): void {
		if (
			!$this->inFederatedPropertyMode() ||
			!self::getSettings()->hasSetting( 'federatedPropertiesSourceScriptUrl' )
		) {
			throw new LogicException(
				'Federated Property services should not be constructed when federatedProperties feature is not enabled or configured.'
			);
		}
	}

	public function inFederatedPropertyMode(): bool {
		return self::getSettings()->getSetting( 'federatedPropertiesEnabled' );
	}

	public function newFederatedPropertiesServiceFactory(): ApiServiceFactory {
		$this->throwLogicExceptionIfFederatedPropertiesNotEnabledAndConfigured();

		global $wgServerName;

		return new ApiServiceFactory(
			self::getSettings()->getSetting( 'federatedPropertiesSourceScriptUrl' ),
			$wgServerName
		);
	}

	public static function getLinkTargetEntityIdLookup( ContainerInterface $services = null ): LinkTargetEntityIdLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LinkTargetEntityIdLookup' );
	}

}
