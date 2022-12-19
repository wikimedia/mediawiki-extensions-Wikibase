<?php

/**
 * Definition of entity types for use with Wikibase.
 * The array returned by the code below is supposed to be merged with the content of
 * lib/WikibaseLib.entitytypes.php.
 * It defines the views used by the repo to display entities of different types.
 *
 * @note: Keep in sync with lib/WikibaseLib.entitytypes.php
 *
 * @note This is bootstrap code, it is executed for EVERY request.
 * Avoid instantiating objects here!
 *
 * @see docs/entitytypes.wiki
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use MediaWiki\MediaWikiServices;
use Wikibase\DataAccess\DatabaseEntitySource;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Serializers\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\DataTypeDefinitions;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Formatters\LabelsProviderEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Interactors\MatchingTermsLookupSearchInteractor;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityArticleIdLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityExistenceChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityRedirectChecker;
use Wikibase\Lib\Store\TitleLookupBasedEntityTitleTextLookup;
use Wikibase\Lib\Store\TitleLookupBasedEntityUrlLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\Api\CombinedEntitySearchHelper;
use Wikibase\Repo\Api\EntityIdSearchHelper;
use Wikibase\Repo\Api\EntityTermSearchHelper;
use Wikibase\Repo\Api\PropertyDataTypeSearchHelper;
use Wikibase\Repo\ChangeOp\Deserialization\ItemChangeOpDeserializer;
use Wikibase\Repo\ChangeOp\Deserialization\PropertyChangeOpDeserializer;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\Content\PropertyContent;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\ItemDiffVisualizer;
use Wikibase\Repo\EntityReferenceExtractors\EntityReferenceExtractorCollection;
use Wikibase\Repo\EntityReferenceExtractors\SiteLinkBadgeItemReferenceExtractor;
use Wikibase\Repo\EntityReferenceExtractors\StatementEntityReferenceExtractor;
use Wikibase\Repo\Hooks\Formatters\DefaultEntityLinkFormatter;
use Wikibase\Repo\ParserOutput\EntityTermsViewFactory;
use Wikibase\Repo\ParserOutput\TermboxFlag;
use Wikibase\Repo\Rdf\FullStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ItemRdfBuilder;
use Wikibase\Repo\Rdf\ItemStubRdfBuilder;
use Wikibase\Repo\Rdf\PropertyRdfBuilder;
use Wikibase\Repo\Rdf\PropertySpecificComponentsRdfBuilder;
use Wikibase\Repo\Rdf\PropertyStubRdfBuilder;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\Rdf\TermsRdfBuilder;
use Wikibase\Repo\Rdf\TruthyStatementRdfBuilderFactory;
use Wikibase\Repo\Rdf\ValueSnakRdfBuilderFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\FingerprintableEntityMetaTagsCreator;
use Wikimedia\Purtle\RdfWriter;

return [
	'item' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		Def::VIEW_FACTORY_CALLBACK => function(
			Language $language,
			TermLanguageFallbackChain $fallbackChain,
			EntityDocument $entity
		) {
			$viewFactory = WikibaseRepo::getViewFactory();
			return $viewFactory->newItemView(
				$language,
				$fallbackChain,
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
			$languageFallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		Def::CONTENT_MODEL_ID => ItemContent::CONTENT_MODEL_ID,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function() {
			return WikibaseRepo::getItemHandler();
		},
		Def::ENTITY_FACTORY_CALLBACK => function() {
			return new Item();
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function() {
			return new ItemChangeOpDeserializer(
				WikibaseRepo::getChangeOpDeserializerFactory()
			);
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function(
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			$services = MediaWikiServices::getInstance();
			$sites = $services->getSiteLookup()->getSites();
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				WikibaseRepo::getDataTypeDefinitions( $services )
					->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				WikibaseRepo::getLogger( $services )
			);

			$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
				$dedupe,
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$mentionedEntityTracker,
				$propertyDataLookup
			);
			$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$mentionedEntityTracker,
				$dedupe,
				$propertyDataLookup
			);
			$siteLinksRdfBuilder = new SiteLinksRdfBuilder( $vocabulary, $writer, $sites );
			$siteLinksRdfBuilder->setDedupeBag( $dedupe );

			$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
			$termsRdfBuilder = new TermsRdfBuilder(
				$vocabulary,
				$writer,
				$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES )
			);

			return new ItemRdfBuilder(
				$flavorFlags,
				$siteLinksRdfBuilder,
				$termsRdfBuilder,
				$truthyStatementRdfBuilderFactory,
				$fullStatementRdfBuilderFactory
			);
		},
		Def::RDF_BUILDER_STUB_FACTORY_CALLBACK => function(
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) {
			$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions();
			$labelPredicates = $entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES );
			$termLookup = WikibaseRepo::getPrefetchingTermLookup();
			$languageFallbackFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$languageCodes = $languageFallbackFactory->newFromContext( RequestContext::getMain() )->getFetchLanguageCodes();

			return new ItemStubRdfBuilder(
				$termLookup,
				$vocabulary,
				$writer,
				$labelPredicates,
				$languageCodes
			);
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
				$claimDiffView
			);

			return new ItemDiffVisualizer(
				$messageLocalizer,
				$siteLookup,
				$entityIdFormatter,
				$basicEntityDiffVisualizer
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			$languageFallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$language = WikibaseRepo::getUserLanguage();
			return new CombinedEntitySearchHelper(
					[
						new EntityIdSearchHelper(
							WikibaseRepo::getEntityLookup(),
							WikibaseRepo::getEntityIdParser(),
							WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
								->newLabelDescriptionLookup( $language ),
							WikibaseRepo::getEntityTypeToRepositoryMapping()
						),
						new EntityTermSearchHelper(
							new MatchingTermsLookupSearchInteractor(
								WikibaseRepo::getMatchingTermsLookupFactory()->getLookupForSource(
									WikibaseRepo::getEntitySourceDefinitions()
										->getDatabaseSourceForEntityType( Item::ENTITY_TYPE )
								),
								$languageFallbackChainFactory,
								WikibaseRepo::getPrefetchingTermLookup(),
								$language->getCode()
							)
						),
					]
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$services = MediaWikiServices::getInstance();
			return new DefaultEntityLinkFormatter(
				$language,
				WikibaseRepo::getEntityTitleTextLookup( $services ),
				$services->getLanguageFactory()
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$languageLabelLookup = WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				WikibaseRepo::getLanguageNameLookupFactory()->getForLanguage( $language ),
				WikibaseRepo::getEntityExistenceChecker(),
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseRepo::getEntityUrlLookup(),
				WikibaseRepo::getEntityRedirectChecker()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new EntityReferenceExtractorCollection( [
				new SiteLinkBadgeItemReferenceExtractor(),
				new StatementEntityReferenceExtractor( WikibaseRepo::getItemUrlParser() ),
			] );
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( DatabaseEntitySource $entitySource ) {
			$termIdsResolver = WikibaseRepo::getTermInLangIdsResolverFactory()
				->getResolverForEntitySource( $entitySource );

			return new PrefetchingItemTermLookup( $termIdsResolver );
		},
		Def::URL_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityUrlLookup( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::EXISTENCE_CHECKER_CALLBACK => function () {
			$services = MediaWikiServices::getInstance();
			return new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
			);
		},
		Def::REDIRECT_CHECKER_CALLBACK => function () {
			return new TitleLookupBasedEntityRedirectChecker( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
	],
	'property' => [
		Def::ARTICLE_ID_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityArticleIdLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
		Def::STORAGE_SERIALIZER_FACTORY_CALLBACK => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		Def::VIEW_FACTORY_CALLBACK => function(
			Language $language,
			TermLanguageFallbackChain $fallbackChain,
			EntityDocument $entity
		) {
			$viewFactory = WikibaseRepo::getViewFactory();
			return $viewFactory->newPropertyView(
				$language,
				$fallbackChain,
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
			$languageFallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		Def::CONTENT_MODEL_ID => PropertyContent::CONTENT_MODEL_ID,
		Def::CONTENT_HANDLER_FACTORY_CALLBACK => function() {
			return WikibaseRepo::getPropertyHandler();
		},
		Def::ENTITY_FACTORY_CALLBACK => function() {
			return Property::newFromType( '' );
		},
		Def::CHANGEOP_DESERIALIZER_CALLBACK => function() {
			return new PropertyChangeOpDeserializer(
				WikibaseRepo::getChangeOpDeserializerFactory()
			);
		},
		Def::RDF_BUILDER_FACTORY_CALLBACK => function(
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			$services = MediaWikiServices::getInstance();
			$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions( $services );
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$valueSnakRdfBuilderFactory = new ValueSnakRdfBuilderFactory(
				WikibaseRepo::getDataTypeDefinitions( $services )
					->getRdfBuilderFactoryCallbacks( DataTypeDefinitions::PREFIXED_MODE ),
				WikibaseRepo::getLogger( $services )
			);

			$termsRdfBuilder = new TermsRdfBuilder(
				$vocabulary,
				$writer,
				$entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES )
			);

			$truthyStatementRdfBuilderFactory = new TruthyStatementRdfBuilderFactory(
				$dedupe,
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$mentionedEntityTracker,
				$propertyDataLookup
			);
			$fullStatementRdfBuilderFactory = new FullStatementRdfBuilderFactory(
				$vocabulary,
				$writer,
				$valueSnakRdfBuilderFactory,
				$mentionedEntityTracker,
				$dedupe,
				$propertyDataLookup
			);

			$dataTypeLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$propertySpecificRdfBuilder = new PropertySpecificComponentsRdfBuilder(
				$vocabulary,
				$writer,
				$dataTypeLookup,
				WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes()
			);

			return new PropertyRdfBuilder(
				$flavorFlags,
				$truthyStatementRdfBuilderFactory,
				$fullStatementRdfBuilderFactory,
				$termsRdfBuilder,
				$propertySpecificRdfBuilder
			);
		},
		Def::RDF_BUILDER_STUB_FACTORY_CALLBACK => function(
			RdfVocabulary $vocabulary,
			RdfWriter $writer
		) {
			$entityTypeDefinitions = WikibaseRepo::getEntityTypeDefinitions();
			$labelPredicates = $entityTypeDefinitions->get( EntityTypeDefinitions::RDF_LABEL_PREDICATES );
			$prefetchingLookup = WikibaseRepo::getPrefetchingTermLookup();
			$propertyDataLookup = WikibaseRepo::getPropertyDataTypeLookup();
			$dataTypes = WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes();
			$languageFallbackFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$languageCodes = $languageFallbackFactory->newFromContext( RequestContext::getMain() )->getFetchLanguageCodes();
			$termsLanguages = new StaticContentLanguages( $languageCodes );

			return new PropertyStubRdfBuilder(
				$prefetchingLookup,
				$propertyDataLookup,
				$termsLanguages,
				$vocabulary,
				$writer,
				$dataTypes,
				$labelPredicates
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			$languageFallbackChainFactory = WikibaseRepo::getLanguageFallbackChainFactory();
			$language = WikibaseRepo::getUserLanguage();
			return new PropertyDataTypeSearchHelper(
				new CombinedEntitySearchHelper(
					[
						new EntityIdSearchHelper(
							WikibaseRepo::getEntityLookup(),
							WikibaseRepo::getEntityIdParser(),

							WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
								->newLabelDescriptionLookup( $language ),
							WikibaseRepo::getEntityTypeToRepositoryMapping()
						),
						new EntityTermSearchHelper(
							new MatchingTermsLookupSearchInteractor(
								WikibaseRepo::getMatchingTermsLookupFactory()->getLookupForSource(
									WikibaseRepo::getEntitySourceDefinitions()
										->getDatabaseSourceForEntityType( Property::ENTITY_TYPE )
								),
								$languageFallbackChainFactory,
								WikibaseRepo::getPrefetchingTermLookup(),
								$language->getCode()
							)
						),
					]
				),
				WikibaseRepo::getPropertyDataTypeLookup()
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$services = MediaWikiServices::getInstance();
			return new DefaultEntityLinkFormatter(
				$language,
				WikibaseRepo::getEntityTitleTextLookup( $services ),
				$services->getLanguageFactory()
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$languageLabelLookup = WikibaseRepo::getFallbackLabelDescriptionLookupFactory()
				->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				WikibaseRepo::getLanguageNameLookupFactory()->getForLanguage( $language ),
				WikibaseRepo::getEntityExistenceChecker(),
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseRepo::getEntityUrlLookup(),
				WikibaseRepo::getEntityRedirectChecker()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new StatementEntityReferenceExtractor( WikibaseRepo::getItemUrlParser() );
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( DatabaseEntitySource $entitySource ) {
			$mwServices = MediaWikiServices::getInstance();

			$cacheSecret = hash( 'sha256', $mwServices->getMainConfig()->get( 'SecretKey' ) );
			$bagOStuff = $mwServices->getLocalServerObjectCache();
			$termIdsResolver = WikibaseRepo::getTermInLangIdsResolverFactory( $mwServices )
				->getResolverForEntitySource( $entitySource );

			$prefetchingPropertyTermLookup = new PrefetchingPropertyTermLookup( $termIdsResolver );

			// If MediaWiki has no local server cache available, return the raw lookup.
			if ( $bagOStuff instanceof EmptyBagOStuff ) {
				return $prefetchingPropertyTermLookup;
			}

			$cache = new SimpleCacheWithBagOStuff(
				$bagOStuff,
				'wikibase.prefetchingPropertyTermLookup.',
				$cacheSecret
			);
			$cache = new StatsdRecordingSimpleCache(
				$cache,
				$mwServices->getStatsdDataFactory(),
				[
					'miss' => 'wikibase.prefetchingPropertyTermLookupCache.miss',
					'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit',
				]
			);

			return new CachingPrefetchingTermLookup(
				$cache,
				$prefetchingPropertyTermLookup,
				WikibaseRepo::getRedirectResolvingLatestRevisionLookup( $mwServices ),
				WikibaseRepo::getTermsLanguages( $mwServices )
			);
		},
		Def::URL_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityUrlLookup( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::EXISTENCE_CHECKER_CALLBACK => function () {
			$services = MediaWikiServices::getInstance();
			return new TitleLookupBasedEntityExistenceChecker(
				WikibaseRepo::getEntityTitleLookup( $services ),
				$services->getLinkBatchFactory()
			);
		},
		Def::REDIRECT_CHECKER_CALLBACK => function () {
			return new TitleLookupBasedEntityRedirectChecker( WikibaseRepo::getEntityTitleLookup() );
		},
		Def::TITLE_TEXT_LOOKUP_CALLBACK => function () {
			return new TitleLookupBasedEntityTitleTextLookup(
				WikibaseRepo::getEntityTitleLookup()
			);
		},
	],
];
