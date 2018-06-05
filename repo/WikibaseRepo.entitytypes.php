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
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */

use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\SerializerFactory;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
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
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EditSectionGenerator;
use Wikibase\View\EntityTermsView;
use Wikimedia\Purtle\RdfWriter;

return [
	'item' => [
		'storage-serializer-factory-callback' => function( SerializerFactory $serializerFactory ) {
			return $serializerFactory->newItemSerializer();
		},
		'view-factory-callback' => function(
			$languageCode,
			LabelDescriptionLookup $labelDescriptionLookup,
			LanguageFallbackChain $fallbackChain,
			EditSectionGenerator $editSectionGenerator,
			EntityTermsView $entityTermsView
		) {
			$viewFactory = WikibaseRepo::getDefaultInstance()->getViewFactory();
			return $viewFactory->newItemView(
				$languageCode,
				$labelDescriptionLookup,
				$fallbackChain,
				$editSectionGenerator,
				$entityTermsView
			);
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
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			// Leaving only EntitySearchTermIndex here
			$repo = WikibaseRepo::getDefaultInstance();
			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] ) {
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
			$languageCode,
			LabelDescriptionLookup $labelDescriptionLookup,
			LanguageFallbackChain $fallbackChain,
			EditSectionGenerator $editSectionGenerator,
			EntityTermsView $entityTermsView
		) {
			$viewFactory = WikibaseRepo::getDefaultInstance()->getViewFactory();
			return $viewFactory->newPropertyView(
				$languageCode,
				$labelDescriptionLookup,
				$fallbackChain,
				$editSectionGenerator,
				$entityTermsView
			);
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
		'entity-search-callback' => function ( WebRequest $request ) {
			// FIXME: this code should be split into extension for T190022
			// Leaving only EntitySearchTermIndex here
			$repo = WikibaseRepo::getDefaultInstance();
			$repoSettings = $repo->getSettings();
			$searchSettings = $repoSettings->getSetting( 'entitySearch' );
			if ( $searchSettings['useCirrus'] ) {
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
		'entity-reference-extractor-callback' => function() {
			return new StatementEntityReferenceExtractor( WikibaseRepo::getDefaultInstance()->getLocalItemUriParser() );
		},
		'fulltext-search-context' => \Wikibase\Repo\Search\Elastic\EntitySearchElastic::CONTEXT_WIKIBASE_FULLTEXT,
	]
];
