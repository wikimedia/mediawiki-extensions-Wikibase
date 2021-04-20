<?php

namespace Wikibase\Repo;

use DataValues\Deserializers\DataValueDeserializer;
use Deserializers\Deserializer;
use Deserializers\DispatchableDeserializer;
use Diff\Comparer\ComparableComparer;
use Diff\Differ\OrderedListDiffer;
use Exception;
use IContextSource;
use Language;
use MediaWiki\MediaWikiServices;
use MWException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Serializers\Serializer;
use SiteLookup;
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
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\Diff\EntityDiffer;
use Wikibase\DataModel\Services\Diff\EntityPatcher;
use Wikibase\DataModel\Services\EntityId\EntityIdComposer;
use Wikibase\DataModel\Services\EntityId\SuffixEntityIdParser;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\TermLookup;
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
use Wikibase\Lib\SettingsArray;
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
use Wikibase\Lib\Store\LookupConstants;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\TermStoreWriterFactory;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsAcquirer;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsLookup;
use Wikibase\Lib\Store\Sql\Terms\TypeIdsResolver;
use Wikibase\Lib\Store\Sql\WikiPageEntityMetaDataAccessor;
use Wikibase\Lib\StringNormalizer;
use Wikibase\Lib\TermFallbackCache\TermFallbackCacheFacade;
use Wikibase\Lib\TermFallbackCacheFactory;
use Wikibase\Lib\Units\UnitConverter;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Api\ApiHelperFactory;
use Wikibase\Repo\ChangeOp\ChangeOpFactoryProvider;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializerFactory;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
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
use Wikibase\Repo\FederatedProperties\ApiServiceFactory;
use Wikibase\Repo\Hooks\Formatters\EntityLinkFormatterFactory;
use Wikibase\Repo\Interactors\ItemMergeInteractor;
use Wikibase\Repo\Interactors\ItemRedirectCreationInteractor;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\LinkedData\EntityDataUriManager;
use Wikibase\Repo\Localizer\ExceptionLocalizer;
use Wikibase\Repo\Notifications\ChangeNotifier;
use Wikibase\Repo\ParserOutput\DispatchingEntityMetaTagsCreatorFactory;
use Wikibase\Repo\ParserOutput\DispatchingEntityViewFactory;
use Wikibase\Repo\ParserOutput\EntityParserOutputGeneratorFactory;
use Wikibase\Repo\Rdf\EntityRdfBuilderFactory;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\Search\Fields\FieldDefinitionsFactory;
use Wikibase\Repo\Store\EntityPermissionChecker;
use Wikibase\Repo\Store\EntityTitleStoreLookup;
use Wikibase\Repo\Store\IdGenerator;
use Wikibase\Repo\Store\Store;
use Wikibase\Repo\Store\TermsCollisionDetector;
use Wikibase\Repo\Store\TermsCollisionDetectorFactory;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\View\EntityIdFormatterFactory;
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
	 * @var WikibaseRepo|null
	 */
	private static $instance = null;

	public static function resetClassStatics() {
		if ( !defined( 'MW_PHPUNIT_TEST' ) ) {
			throw new Exception(
				'Cannot reset WikibaseRepo class statics outside of tests.'
			);
		}
		self::$instance = null;
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
	 */
	public static function getDefaultValidatorBuilders( ContainerInterface $services = null ): ValidatorBuilders {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DefaultValidatorBuilders' );
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use WikibaseRepo::getSnakFormatterFactory() instead!
	 */
	public static function getDefaultValueFormatterBuilders( ContainerInterface $services = null ): WikibaseValueFormatterBuilders {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DefaultValueFormatterBuilders' );
	}

	public static function getKartographerEmbeddingHandler( ContainerInterface $services = null ): ?CachingKartographerEmbeddingHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.KartographerEmbeddingHandler' );
	}

	/**
	 * @deprecated use {@link LanguageNameUtils} instead
	 */
	public static function getLanguageNameLookup( ContainerInterface $services = null ): LanguageNameLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LanguageNameLookup' );
	}

	/**
	 * @warning This is for use with bootstrap code in WikibaseRepo.datatypes.php only!
	 * Program logic should use {@link WikibaseRepo::getSnakFormatterFactory()} instead!
	 *
	 * @return WikibaseSnakFormatterBuilders
	 */
	public static function getDefaultSnakFormatterBuilders( ContainerInterface $services = null ): WikibaseSnakFormatterBuilders {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.DefaultSnakFormatterBuilders' );
	}

	public function __construct() {
		// TODO remove
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
	 * Get a caching entity revision lookup.
	 *
	 * If you need different caching behavior, use {@link Store::getEntityRevisionLookup()} instead.
	 */
	public static function getEntityRevisionLookup( $servicesOrCache = null ): EntityRevisionLookup {
		// For backwards compatibility, we temporarily support several calling conventions:
		// getEntityRevisionLookup() – default $services, $cache
		// getEntityRevisionLookup( $services ) – default $cache
		// getEntityRevisionLookup( $cache ) – default $services (deprecated)
		// TODO remove all this and change the method syntax to the standard form
		// public static function getEntityRevisionLookup( ContainerInterface $services = null ): EntityRevisionLookup

		if ( $servicesOrCache === null ) {
			$servicesOrCache = MediaWikiServices::getInstance();
		}

		if ( $servicesOrCache instanceof ContainerInterface ) {
			$services = $servicesOrCache;
			return $services->get( 'WikibaseRepo.EntityRevisionLookup' );
		} else {
			wfDeprecated(
				__METHOD__ . ' with non-default $cache',
				'1.37',
				'WikibaseRepo'
			);
			$cache = $servicesOrCache;
			return self::getStore()->getEntityRevisionLookup( $cache );
		}
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
		$store = self::getStore();

		return new ItemRedirectCreationInteractor(
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEntityStore(),
			self::getEntityPermissionChecker(),
			self::getSummaryFormatter(),
			$user,
			self::getEditFilterHookRunner(),
			$store->getEntityRedirectLookup(),
			self::getEntityTitleStoreLookup()
		);
	}

	public static function getEditFilterHookRunner( ContainerInterface $services = null ): EditFilterHookRunner {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EditFilterHookRunner' );
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

	public static function getPropertyDataTypeLookup( ContainerInterface $services = null ): PropertyDataTypeLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyDataTypeLookup' );
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
	public static function getEntityLookup( $servicesOrCache = null, $lookupMode = null ): EntityLookup {
		// For backwards compatibility, we temporarily support several calling conventions:
		// getEntityLookup() – default $services, $cache, $lookupMode
		// getEntityLookup( $services ) – default $cache, $lookupMode
		// getEntityLookup( $cache, $lookupMode ) – default $services (deprecated)
		// TODO remove all this and change the method syntax to the standard form
		// public static function getEntityLookup( ContainerInterface $services = null ): EntityLookup

		if ( $lookupMode !== null ) {
			wfDeprecated(
				__METHOD__ . ' with non-default $cache or $lookupMode',
				'1.36',
				'WikibaseRepo'
			);
			$cache = $servicesOrCache;
			return self::getStore()->getEntityLookup( $cache, $lookupMode );
		}

		if ( $servicesOrCache === null ) {
			$servicesOrCache = MediaWikiServices::getInstance();
		}

		if ( $servicesOrCache instanceof ContainerInterface ) {
			$services = $servicesOrCache;
			return $services->get( 'WikibaseRepo.EntityLookup' );
		} else {
			wfDeprecated(
				__METHOD__ . ' with non-default $cache or $lookupMode',
				'1.36',
				'WikibaseRepo'
			);
			$cache = $servicesOrCache;
			$lookupMode = LookupConstants::LATEST_FROM_REPLICA; // we already know it’s null, i.e. default, from earlier
			return self::getStore()->getEntityLookup( $cache, $lookupMode );
		}
	}

	public static function getSnakFactory( ContainerInterface $services = null ): SnakFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.SnakFactory' );
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

	public static function getChangeOpFactoryProvider( ContainerInterface $services = null ): ChangeOpFactoryProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ChangeOpFactoryProvider' );
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

	public static function getChangeOpDeserializerFactory( ContainerInterface $services = null ): ChangeOpDeserializerFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ChangeOpDeserializerFactory' );
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
	 */
	public static function getSnakFormatterFactory( ContainerInterface $services = null ): OutputFormatSnakFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.SnakFormatterFactory' );
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

	public static function getExceptionLocalizer( ContainerInterface $services = null ): ExceptionLocalizer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ExceptionLocalizer' );
	}

	public static function getSummaryFormatter( ContainerInterface $services = null ): SummaryFormatter {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.SummaryFormatter' );
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

	public static function getValidatorErrorLocalizer( ContainerInterface $services = null ): ValidatorErrorLocalizer {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ValidatorErrorLocalizer' );
	}

	/**
	 * @return SiteLookup
	 */
	private function getSiteLookup() {
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
	public static function getEnabledEntityTypes( ContainerInterface $services = null ): array {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EnabledEntityTypes' );
	}

	public static function getLocalEntityTypes( ContainerInterface $services = null ): array {
		// List of entity and sub-entity type identifiers (typically "item" and "property")
		// that are configured in WikibaseRepo.entitytypes.php and enabled via the
		// $wgWBRepoSettings['entityNamespaces'] setting.
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalEntityTypes' );
	}

	public static function getEntityContentDataCodec( ContainerInterface $services = null ): EntityContentDataCodec {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityContentDataCodec' );
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

	public static function getItemHandler( ContainerInterface $services = null ): ItemHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ItemHandler' );
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

	public static function getFieldDefinitionsFactory( ContainerInterface $services = null ): FieldDefinitionsFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.FieldDefinitionsFactory' );
	}

	public static function getPropertyHandler( ContainerInterface $services = null ): PropertyHandler {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyHandler' );
	}

	public static function getPropertyInfoBuilder( ContainerInterface $services = null ): PropertyInfoBuilder {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.PropertyInfoBuilder' );
	}

	/** @internal */
	public static function getLegacyFormatDetectorCallback( ContainerInterface $services = null ): ?callable {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LegacyFormatDetectorCallback' );
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ApiHelperFactory
	 */
	public function getApiHelperFactory( IContextSource $context ) {
		$services = MediaWikiServices::getInstance();
		$store = self::getStore( $services );

		return new ApiHelperFactory(
			self::getEntityTitleStoreLookup(),
			self::getExceptionLocalizer( $services ),
			self::getPropertyDataTypeLookup( $services ),
			$this->getSiteLookup(),
			self::getSummaryFormatter( $services ),
			$store->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEditEntityFactory( $services ),
			self::getBaseDataModelSerializerFactory( $services ),
			self::getAllTypesEntitySerializer( $services ),
			self::getEntityIdParser( $services ),
			$services->getPermissionManager(),
			$services->getRevisionLookup(),
			$services->getTitleFactory(),
			$store->getEntityByLinkedTitleLookup(),
			self::getEntityFactory(),
			self::getEntityStore()
		);
	}

	public static function getEditEntityFactory(
		ContainerInterface $services = null
	): MediawikiEditEntityFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EditEntityFactory' );
	}

	/**
	 * @param IContextSource $context
	 *
	 * @return ItemMergeInteractor
	 */
	public function newItemMergeInteractor( IContextSource $context ) {
		$user = $context->getUser();

		return new ItemMergeInteractor(
			self::getChangeOpFactoryProvider()->getMergeFactory(),
			self::getStore()
				->getEntityRevisionLookup( Store::LOOKUP_CACHING_DISABLED ),
			self::getEntityStore(),
			self::getEntityPermissionChecker(),
			self::getSummaryFormatter(),
			$user,
			$this->newItemRedirectCreationInteractor( $user, $context ),
			self::getEntityTitleStoreLookup(),
			MediaWikiServices::getInstance()->getPermissionManager()
		);
	}

	public static function getEntityNamespaceLookup( ContainerInterface $services = null ): EntityNamespaceLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityNamespaceLookup' );
	}

	public static function getLocalEntityNamespaceLookup( ContainerInterface $services = null ): EntityNamespaceLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LocalEntityNamespaceLookup' );
	}

	public static function getEntityIdHtmlLinkFormatterFactory(
		ContainerInterface $services = null
	): EntityIdFormatterFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityIdHtmlLinkFormatterFactory' );
	}

	public static function getEntityViewFactory( ContainerInterface $services = null ): DispatchingEntityViewFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityViewFactory' );
	}

	public static function getEntityMetaTagsCreatorFactory(
		ContainerInterface $services = null
	): DispatchingEntityMetaTagsCreatorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityMetaTagsCreatorFactory' );
	}

	public static function getEntityDataFormatProvider( ContainerInterface $services = null ): EntityDataFormatProvider {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityDataFormatProvider' );
	}

	public static function getEntityDataUriManager( ContainerInterface $services = null ): EntityDataUriManager {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityDataUriManager' );
	}

	public static function getEntityParserOutputGeneratorFactory(
		ContainerInterface $services = null
	): EntityParserOutputGeneratorFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityParserOutputGeneratorFactory' );
	}

	public static function getViewFactory( ContainerInterface $services = null ): ViewFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.ViewFactory' );
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

	public static function getCachingCommonsMediaFileNameLookup( ContainerInterface $services = null ): CachingCommonsMediaFileNameLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.CachingCommonsMediaFileNameLookup' );
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

		$htmlFormatterFactory = self::getEntityIdHtmlLinkFormatterFactory();
		$entityIdFormatter = $htmlFormatterFactory->getEntityIdFormatter( $contextSource->getLanguage() );

		$formatterFactory = self::getSnakFormatterFactory();
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
	public static function getEntityTypeToRepositoryMapping( ContainerInterface $services = null ): array {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.EntityTypeToRepositoryMapping' );
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
	 * @return string[]
	 */
	public static function getFulltextSearchTypes( ContainerInterface $services = null ): array {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.FulltextSearchTypes' );
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

	public static function getFederatedPropertiesServiceFactory( ContainerInterface $services = null ): ApiServiceFactory {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.FederatedPropertiesServiceFactory' );
	}

	public static function getLinkTargetEntityIdLookup( ContainerInterface $services = null ): LinkTargetEntityIdLookup {
		return ( $services ?: MediaWikiServices::getInstance() )
			->get( 'WikibaseRepo.LinkTargetEntityIdLookup' );
	}

}
