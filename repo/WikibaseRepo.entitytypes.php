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

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\InProcessCachingDataTypeLookup;
use Wikibase\Lib\LabelsProviderEntityIdHtmlLinkFormatter;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Repo\Diff\BasicEntityDiffVisualizer;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;
use Wikibase\Repo\Diff\ItemDiffVisualizer;
use Wikibase\LanguageFallbackChain;
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
use Wikibase\Repo\Search\Elastic\Fields\DescriptionsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\ItemFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\LabelsProviderFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\PropertyFieldDefinitions;
use Wikibase\Repo\Search\Elastic\Fields\StatementProviderFieldDefinitions;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\View\FingerprintableEntityMetaTagsCreator;
use Wikimedia\Purtle\RdfWriter;

return [
	'item' => [
		'storage-serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		'view-factory-callback' => function(
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
		'meta-tags-creator-callback' => function ( $userLanguage ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		'content-model-id' => CONTENT_MODEL_WIKIBASE_ITEM,
		'content-handler-factory-callback' => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return $wikibaseRepo->newItemHandler();
		},
		'entity-factory-callback' => function() {
			return new Item();
		},
		'changeop-deserializer-callback' => function() {
			return new ItemChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getChangeOpDeserializerFactory()
			);
		},
		'rdf-builder-factory-callback' => function(
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
		'entity-diff-visualizer-callback' => function (
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
		'search-field-definitions' => function ( array $languageCodes, SettingsArray $searchSettings ) {
			$repo = WikibaseRepo::getDefaultInstance();
			return new ItemFieldDefinitions( [
				new LabelsProviderFieldDefinitions( $languageCodes ),
				new DescriptionsProviderFieldDefinitions( $languageCodes,
					$searchSettings->getSetting( 'entitySearch' ) ),
				StatementProviderFieldDefinitions::newFromSettings(
					new InProcessCachingDataTypeLookup( $repo->getPropertyDataTypeLookup() ),
					$repo->getDataTypeDefinitions()->getSearchIndexDataFormatterCallbacks(),
					$searchSettings
				)
			] );
		},
			'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			// Leaving only EntitySearchTermIndex here
			$repo = WikibaseRepo::getDefaultInstance();

			$itemSource = $repo->getEntitySourceDefinitions()->getSourceForEntityType( 'item' );
			if ( $itemSource && $itemSource->getApiEndpoint() && $itemSource->useApiForSearch() ) {
				// TODO return API based EntitySearchHelper implementation
			}

			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] && !$repoSettings->getSetting( 'disableCirrus' ) ) {
				return new Wikibase\Repo\Search\Elastic\EntitySearchElastic(
					$repo->getLanguageFallbackChainFactory(),
					$repo->getEntityIdParser(),
					$repo->getUserLanguage(),
					$repo->getContentModelMappings(),
					$searchSettings,
					$request
				);
			} else {
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
			}
		},
		'link-formatter-callback' => function( Language $language ) {
			return new DefaultEntityLinkFormatter( $language );
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				$repo->getLanguageNameLookup()
			);
		},
		'entity-reference-extractor-callback' => function() {
			return new EntityReferenceExtractorCollection( [
				new SiteLinkBadgeItemReferenceExtractor(),
				new StatementEntityReferenceExtractor( WikibaseRepo::getDefaultInstance()->getLocalItemUriParser() )
			] );
		},
		'fulltext-search-context' => \Wikibase\Repo\Search\Elastic\EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT,
	],
	'property' => [
		'storage-serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newPropertySerializer();
		},
		'view-factory-callback' => function(
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
		'meta-tags-creator-callback' => function ( Language $userLanguage ) {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			$languageFallbackChainFactory = $wikibaseRepo->getLanguageFallbackChainFactory();
			$languageFallbackChain = $languageFallbackChainFactory->newFromLanguage( $userLanguage );
			return new FingerprintableEntityMetaTagsCreator( $languageFallbackChain );
		},
		'content-model-id' => CONTENT_MODEL_WIKIBASE_PROPERTY,
		'content-handler-factory-callback' => function() {
			$wikibaseRepo = WikibaseRepo::getDefaultInstance();
			return $wikibaseRepo->newPropertyHandler();
		},
		'entity-factory-callback' => function() {
			return Property::newFromType( '' );
		},
		'changeop-deserializer-callback' => function() {
			return new PropertyChangeOpDeserializer(
				WikibaseRepo::getDefaultInstance()->getChangeOpDeserializerFactory()
			);
		},
		'rdf-builder-factory-callback' => function(
			$flavorFlags,
			RdfVocabulary $vocabulary,
			RdfWriter $writer,
			$mentionedEntityTracker,
			$dedupe
		) {
			return new PropertyRdfBuilder(
				$vocabulary,
				$writer
			);
		},
		'search-field-definitions' => function ( array $languageCodes, SettingsArray $searchSettings ) {
			$repo = WikibaseRepo::getDefaultInstance();
			return new PropertyFieldDefinitions( [
				new LabelsProviderFieldDefinitions( $languageCodes ),
				new DescriptionsProviderFieldDefinitions( $languageCodes,
					$searchSettings->getSetting( 'entitySearch' ) ),
				StatementProviderFieldDefinitions::newFromSettings(
					new InProcessCachingDataTypeLookup( $repo->getPropertyDataTypeLookup() ),
					$repo->getDataTypeDefinitions()->getSearchIndexDataFormatterCallbacks(),
					$searchSettings
				)
			] );
		},
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			// Leaving only EntitySearchTermIndex here
			$repo = WikibaseRepo::getDefaultInstance();


			$itemSource = $repo->getEntitySourceDefinitions()->getSourceForEntityType( 'property' );
			if ( $itemSource && $itemSource->getApiEndpoint() && $itemSource->useApiForSearch() ) {
				// TODO return API based EntitySearchHelper implementation
			}

			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] && !$repoSettings->getSetting( 'disableCirrus' ) ) {
				return new Wikibase\Repo\Search\Elastic\EntitySearchElastic(
					$repo->getLanguageFallbackChainFactory(),
					$repo->getEntityIdParser(),
					$repo->getUserLanguage(),
					$repo->getContentModelMappings(),
					$searchSettings,
					$request
				);
			} else {
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
			}
		},
		'link-formatter-callback' => function( Language $language ) {
			return new DefaultEntityLinkFormatter( $language );
		},
		'entity-id-html-link-formatter-callback' => function( Language $language ) {
			$repo = WikibaseRepo::getDefaultInstance();
			$languageLabelLookupFactory = $repo->getLanguageFallbackLabelDescriptionLookupFactory();
			$languageLabelLookup = $languageLabelLookupFactory->newLabelDescriptionLookup( $language );
			return new LabelsProviderEntityIdHtmlLinkFormatter(
				$languageLabelLookup,
				$repo->getEntityTitleLookup(),
				$repo->getLanguageNameLookup()
			);
		},
		'entity-reference-extractor-callback' => function() {
			return new StatementEntityReferenceExtractor( WikibaseRepo::getDefaultInstance()->getLocalItemUriParser() );
		},
		'fulltext-search-context' => \Wikibase\Repo\Search\Elastic\EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT,
	]
];
