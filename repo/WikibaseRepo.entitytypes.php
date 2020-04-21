<?php

/**
 * Definition of entity types for use with Wikibase.
 * The array returned by the code below is supposed to be merged with the content of
 * lib/WikibaseLib.entitytypes.php.
 * It defines the views used by the repo to display entities of different types.
 *
 * @note: Keep in sync with lib/WikibaseLib.entitytypes.php
 *
 * @note: This is bootstrap code, it is executed for EVERY request. Avoid instantiating
 * objects or loading classes here!
 *
 * @see docs/entiytypes.wiki
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\LanguageFallbackChain;
use Wikibase\Lib\Formatters\LabelsProviderEntityIdHtmlLinkFormatter;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\BufferingTermIndexTermLookup;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTermInLangIdsResolver;
use Wikibase\Lib\Store\Sql\Terms\DatabaseTypeIdsStore;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\Sql\Terms\TermStoresDelegatingPrefetchingItemTermLookup;
use Wikibase\Lib\Store\UncachedTermsPrefetcher;
use Wikibase\Lib\WikibaseContentLanguages;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\ItemDiffVisualizer;
use Wikibase\Rdf\NullEntityRdfBuilder;
use Wikibase\Rdf\PropertyRdfBuilder;
use Wikibase\Rdf\RdfProducer;
use Wikibase\Rdf\RdfVocabulary;
use Wikibase\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\ParserOutput\EntityTermsViewFactory;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\FingerprintableEntityMetaTagsCreator;
use Wikimedia\Purtle\RdfWriter;

return [
	'item' => [
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function() {
			$mediaWikiServices = MediaWikiServices::getInstance();
			$logger = LoggerFactory::getInstance( 'Wikibase' );
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			$termIndex = $wikibaseRepo->getStore()->getTermIndex();

			$termIndexBackedTermLookup = new BufferingTermIndexTermLookup(
				$termIndex, // TODO: customize buffer sizes
				1000
			);

			$itemSourceDbName = $wikibaseRepo
				->getEntitySourceDefinitions()
				->getSourceForEntityType( Item::ENTITY_TYPE )
				->getDatabaseName();
			$loadBalancer = $mediaWikiServices->getDBLoadBalancerFactory()->getMainLB( $itemSourceDbName );

			$databaseTypeIdsStore = new DatabaseTypeIdsStore(
				$loadBalancer,
				$mediaWikiServices->getMainWANObjectCache(),
				$itemSourceDbName,
				$logger
			);

			$termIdsResolver = new DatabaseTermInLangIdsResolver(
				$databaseTypeIdsStore,
				$databaseTypeIdsStore,
				$loadBalancer,
				$itemSourceDbName,
				$logger
			);
			return new TermStoresDelegatingPrefetchingItemTermLookup(
				$wikibaseRepo->getDataAccessSettings(),
				new PrefetchingItemTermLookup( $loadBalancer, $termIdsResolver, $itemSourceDbName ),
				$termIndexBackedTermLookup
			);
		},
		Def::VIEW_FACTORY_CALLBACK => function(
			Language $language,
			LanguageFallbackChain $fallbackChain,
			EntityDocument $entity,
			EntityInfo $entityInfo
		) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$viewFactory = $wikibaseRepo->getViewFactory();
			return $viewFactory->newItemView(
				$language,
				$fallbackChain,
				$entityInfo,
				( new EntityTermsViewFactory() )
					->newEntityTermsView(
						$entity,
						$language,
						$fallbackChain,
						TermboxFlag::getInstance()->shouldRenderTermbox()
					)
			);
		},
		Def::META_TAGS_CREATOR_CALLBACK => function ( $userLanguage ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		Def::CONTENT_MODEL_ID => CONTENT_MODEL_WIKIBASE_ITEM,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return $wikibaseRepo->newItemHandler();
		},
		Def::ENTITY_FACTORY_CALLBACK => function() {
			return new Item();
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function() {
			return new ItemChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getChangeOpDeserializerFactory()
			);
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function(
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			if ( $flavorFlags & RdfProducer::PRODUCE_SITELINKS ) {
				$sites = WikibaseRepo::getDefaultInstance()->getSiteLookup()->getSites();
				// Since the only extra mapping needed for Items are site links,
				// we just return the SiteLinksRdfBuilder directly,
				// instead of defining an ItemRdfBuilder
				$builder = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
				$builder->setDedupeBag( $dedupe );
				return $builder;
			}
			return new NullEntityRdfBuilder();
		},
		Def::ENTITY_DIFF_VISUALIZER_CALLBACK => function (
			MessageLocalizer $messageLocalizer,
			ClaimDiffer $claimDiffer,
			ClaimDifferenceVisualizer $claimDiffView,
			SiteLookup $siteLookup,
			EntityIdFormatter $entityIdFormatter
		) {
			$basicEntityDiffVisualizer = new BasicEntityDiffVisualizer(
				$messageLocalizer,
				$claimDiffer,
				$claimDiffView,
				$siteLookup,
				$entityIdFormatter
			);

			return new ItemDiffVisualizer(
				$messageLocalizer,
				$claimDiffer,
				$claimDiffView,
				$siteLookup,
				$entityIdFormatter,
				$basicEntityDiffVisualizer
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$repoSettings = $repo->getSettings();
			if ( !$repoSettings->getSetting( 'useTermsTableSearchFields' ) ) {
					wfLogWarning(
						'Using wb_terms table for wbsearchentities API action ' .
						'but not using search-related fields of terms table. ' .
						'This results in degraded search experience, ' .
						'please enable the useTermsTableSearchFields setting.'
					);
			}
			return new Wikibase\Repo\Api\CombinedEntitySearchHelper(
					[
						new Wikibase\Repo\Api\EntityIdSearchHelper(
							$repo->getEntityLookup(),
							$repo->getEntityIdParser(),
							new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
								$repo->getTermLookup(),
								$repo->getLanguageFallbackChainFactory()->newFromLanguage( $repo->getUserLanguage() )
							),
							$repo->getEntityTypeToRepositoryMapping()
						),
						new Wikibase\Repo\Api\EntityTermSearchHelper(
							$repo->newTermSearchInteractor( $repo->getUserLanguage()->getCode() )
						)
					]
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			return new DefaultEntityLinkFormatter( $language );
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				$repo->getLanguageNameLookup()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new EntityReferenceExtractorCollection( [
				new SiteLinkBadgeItemReferenceExtractor(),
				new StatementEntityReferenceExtractor( WikibaseRepo::getDefaultInstance()->getItemUrlParser() )
			] );
		},
	],
	'property' => [
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function() {
			global $wgSecretKey;
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();

			if ( $wikibaseRepo->getDataAccessSettings()->useNormalizedPropertyTerms() ) {
				$cacheSecret = hash( 'sha256', $wgSecretKey );

				$cache = new SimpleCacheWithBagOStuff(
					MediaWikiServices::getInstance()->getLocalServerObjectCache(),
					'wikibase.prefetchingPropertyTermLookup.',
					$cacheSecret
				);
				$cache = new StatsdRecordingSimpleCache(
					$cache,
					MediaWikiServices::getInstance()->getStatsdDataFactory(),
					[
						'miss' => 'wikibase.prefetchingPropertyTermLookupCache.miss',
						'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit'
					]
				);
				$redirectResolvingRevisionLookup = new RedirectResolvingLatestRevisionLookup( $wikibaseRepo->getEntityRevisionLookup() );
				$source = $wikibaseRepo->getEntitySourceDefinitions()
					->getSourceForEntityType( Property::ENTITY_TYPE );
				$propertySourceDbName = $source->getDatabaseName();
				$loadBalancer = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()->getMainLB( $propertySourceDbName );

				return new CachingPrefetchingTermLookup(
					$cache,
					new UncachedTermsPrefetcher(
						new PrefetchingPropertyTermLookup(
							$loadBalancer,
							$wikibaseRepo->getDatabaseTermInLangIdsResolver( $source ),
							$propertySourceDbName
						),
						$redirectResolvingRevisionLookup,
						60 // 1 minute ttl
					),
					$redirectResolvingRevisionLookup,
					WikibaseContentLanguages::getDefaultInstance()->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
				);
			}

			return new BufferingTermIndexTermLookup(
				$wikibaseRepo->getStore()->getTermIndex(), // TODO: customize buffer sizes
				1000
			);
		},
		Def::VIEW_FACTORY_CALLBACK => function(
			Language $language,
			LanguageFallbackChain $fallbackChain,
			EntityDocument $entity,
			EntityInfo $entityInfo
		) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$viewFactory = $wikibaseRepo->getViewFactory();
			return $viewFactory->newPropertyView(
				$language,
				$fallbackChain,
				$entityInfo,
				( new EntityTermsViewFactory() )
					->newEntityTermsView(
						$entity,
						$language,
						$fallbackChain,
						TermboxFlag::getInstance()->shouldRenderTermbox()
					)
			);
		},
		Def::META_TAGS_CREATOR_CALLBACK => function ( Language $userLanguage ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		Def::CONTENT_MODEL_ID => CONTENT_MODEL_WIKIBASE_PROPERTY,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return $wikibaseRepo->newPropertyHandler();
		},
		Def::ENTITY_FACTORY_CALLBACK => function() {
			return Property::newFromType( '' );
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function() {
			return new PropertyChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getChangeOpDeserializerFactory()
			);
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function(
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			return new PropertyRdfBuilder(
				$vocabulary,
				$writer,
				WikibaseRepo::getDefaultInstance()->getDataTypeDefinitions()->getRdfDataTypes()
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$repoSettings = $repo->getSettings();
			if ( !$repoSettings->getSetting( 'useTermsTableSearchFields' ) ) {
				wfLogWarning(
					'Using wb_terms table for wbsearchentities API action ' .
					'but not using search-related fields of terms table. ' .
					'This results in degraded search experience, ' .
					'please enable the useTermsTableSearchFields setting.'
				);
			}

			return new \Wikibase\Repo\Api\PropertyDataTypeSearchHelper(
				new Wikibase\Repo\Api\CombinedEntitySearchHelper(
					[
						new Wikibase\Repo\Api\EntityIdSearchHelper(
							$repo->getEntityLookup(),
							$repo->getEntityIdParser(),
							new Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup(
								$repo->getTermLookup(),
								$repo->getLanguageFallbackChainFactory()->newFromLanguage( $repo->getUserLanguage() )
							),
							$repo->getEntityTypeToRepositoryMapping()
						),
						new Wikibase\Repo\Api\EntityTermSearchHelper(
							$repo->newTermSearchInteractor( $repo->getUserLanguage()->getCode() )
						)
					]
				),
				$repo->getPropertyDataTypeLookup()
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			return new DefaultEntityLinkFormatter( $language );
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				$repo->getLanguageNameLookup()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new StatementEntityReferenceExtractor( WikibaseRepo::getDefaultInstance()->getItemUrlParser() );
		},
	]
];
