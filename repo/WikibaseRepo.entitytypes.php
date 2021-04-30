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
use Wikibase\DataAccess\SingleEntitySourceServices;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\Lib\EntityTypeDefinitions as Def;
use Wikibase\Lib\Formatters\LabelsProviderEntityIdHtmlLinkFormatter;
use Wikibase\Lib\SimpleCacheWithBagOStuff;
use Wikibase\Lib\StatsdRecordingSimpleCache;
use Wikibase\Lib\Store\CachingPrefetchingTermLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\RedirectResolvingLatestRevisionLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingItemTermLookup;
use Wikibase\Lib\Store\Sql\Terms\PrefetchingPropertyTermLookup;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Lib\WikibaseContentLanguages;
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
use Wikibase\Repo\Rdf\NullEntityRdfBuilder;
use Wikibase\Repo\Rdf\PropertyRdfBuilder;
use Wikibase\Repo\Rdf\RdfProducer;
use Wikibase\Repo\Rdf\RdfVocabulary;
use Wikibase\Repo\Rdf\SiteLinksRdfBuilder;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\FingerprintableEntityMetaTagsCreator;
use Wikimedia\Purtle\RdfWriter;

return [
	'item' => [
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
			if ( $flavorFlags & RdfProducer::PRODUCE_SITELINKS ) {
				$sites = MediaWikiServices::getInstance()->getSiteLookup()->getSites();
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
			return new CombinedEntitySearchHelper(
					[
						new EntityIdSearchHelper(
							WikibaseRepo::getEntityLookup(),
							WikibaseRepo::getEntityIdParser(),
							new LanguageFallbackLabelDescriptionLookup(
								WikibaseRepo::getTermLookup(),
								WikibaseRepo::getLanguageFallbackChainFactory()
									->newFromLanguage( WikibaseRepo::getUserLanguage() )
							),
							WikibaseRepo::getEntityTypeToRepositoryMapping()
						),
						new EntityTermSearchHelper(
							WikibaseRepo::getTermSearchInteractorFactory()
								->newInteractor( WikibaseRepo::getUserLanguage()->getCode() )
						)
					]
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			return new DefaultEntityLinkFormatter(
				$language,
				WikibaseRepo::getEntityTitleTextLookup()
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$languageLabelLookupFactory = WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				WikibaseRepo::getLanguageNameLookup(),
				WikibaseRepo::getEntityExistenceChecker(),
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseRepo::getEntityUrlLookup(),
				WikibaseRepo::getEntityRedirectChecker()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new EntityReferenceExtractorCollection( [
				new SiteLinkBadgeItemReferenceExtractor(),
				new StatementEntityReferenceExtractor( WikibaseRepo::getItemUrlParser() )
			] );
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( SingleEntitySourceServices $entitySourceServices ) {
			$termIdsResolver = $entitySourceServices->getTermInLangIdsResolver();
			return new PrefetchingItemTermLookup( $termIdsResolver );
		},
	],
	'property' => [
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
			return new PropertyRdfBuilder(
				$vocabulary,
				$writer,
				WikibaseRepo::getDataTypeDefinitions()->getRdfDataTypes()
			);
		},
		Def::ENTITY_SEARCH_CALLBACK => function ( WebRequest $request ) {
			return new PropertyDataTypeSearchHelper(
				new CombinedEntitySearchHelper(
					[
						new EntityIdSearchHelper(
							WikibaseRepo::getEntityLookup(),
							WikibaseRepo::getEntityIdParser(),
							new LanguageFallbackLabelDescriptionLookup(
								WikibaseRepo::getTermLookup(),
								WikibaseRepo::getLanguageFallbackChainFactory()
									->newFromLanguage( WikibaseRepo::getUserLanguage() )
							),
							WikibaseRepo::getEntityTypeToRepositoryMapping()
						),
						new EntityTermSearchHelper(
							WikibaseRepo::getTermSearchInteractorFactory()
								->newInteractor( WikibaseRepo::getUserLanguage()->getCode() )
						)
					]
				),
				WikibaseRepo::getPropertyDataTypeLookup()
			);
		},
		Def::LINK_FORMATTER_CALLBACK => function( Language $language ) {
			return new DefaultEntityLinkFormatter(
				$language,
				WikibaseRepo::getEntityTitleTextLookup()
			);
		},
		Def::ENTITY_ID_HTML_LINK_FORMATTER_CALLBACK => function( Language $language ) {
			$languageLabelLookupFactory = WikibaseRepo::getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				WikibaseRepo::getLanguageNameLookup(),
				WikibaseRepo::getEntityExistenceChecker(),
				WikibaseRepo::getEntityTitleTextLookup(),
				WikibaseRepo::getEntityUrlLookup(),
				WikibaseRepo::getEntityRedirectChecker()
			);
		},
		Def::ENTITY_REFERENCE_EXTRACTOR_CALLBACK => function() {
			return new StatementEntityReferenceExtractor( WikibaseRepo::getItemUrlParser() );
		},
		Def::PREFETCHING_TERM_LOOKUP_CALLBACK => function ( SingleEntitySourceServices $entitySourceServices ) {
			global $wgSecretKey;

			$mwServices = MediaWikiServices::getInstance();
			$cacheSecret = hash( 'sha256', $wgSecretKey );
			$bagOStuff = $mwServices->getLocalServerObjectCache();

			$prefetchingPropertyTermLookup = new PrefetchingPropertyTermLookup(
				$entitySourceServices->getTermInLangIdsResolver()
			);

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
					'hit' => 'wikibase.prefetchingPropertyTermLookupCache.hit'
				]
			);
			$redirectResolvingRevisionLookup = new RedirectResolvingLatestRevisionLookup(
				$entitySourceServices->getEntityRevisionLookup()
			);

			return new CachingPrefetchingTermLookup(
				$cache,
				$prefetchingPropertyTermLookup,
				$redirectResolvingRevisionLookup,
				WikibaseContentLanguages::getDefaultInstance()
					->getContentLanguages( WikibaseContentLanguages::CONTEXT_TERM )
			);
		},
	]
];
