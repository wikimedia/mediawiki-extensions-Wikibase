<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use SpecialPage;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Services\Lookup\EntityRetrievingTermLookup;
use Wikibase\DataModel\Services\Lookup\InMemoryEntityLookup;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\DataModel\Term\TermList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilder;
use Wikibase\Lib\Store\EntityInfoTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * Creates the parser output for an entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class EntityParserOutputGenerator {

	/**
	 * @var DispatchingEntityViewFactory
	 */
	private $entityViewFactory;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	private $configBuilder;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var EntityInfoBuilder
	 */
	private $entityInfoBuilder;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var TemplateFactory
	 */
	private $templateFactory;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var ParserOutputDataUpdater[]
	 */
	private $dataUpdaters;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityInfoBuilder $entityInfoBuilder
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param TemplateFactory $templateFactory
	 * @param LocalizedTextProvider $textProvider
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param ParserOutputDataUpdater[] $dataUpdaters
	 * @param string $languageCode
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		ParserOutputJsConfigBuilder $configBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityInfoBuilder $entityInfoBuilder,
		LanguageFallbackChain $languageFallbackChain,
		TemplateFactory $templateFactory,
		LocalizedTextProvider $textProvider,
		EntityDataFormatProvider $entityDataFormatProvider,
		array $dataUpdaters,
		$languageCode
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->configBuilder = $configBuilder;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityInfoBuilder = $entityInfoBuilder;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->templateFactory = $templateFactory;
		$this->textProvider = $textProvider;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->dataUpdaters = $dataUpdaters;
		$this->languageCode = $languageCode;
	}

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @param EntityDocument $entity
	 * @param bool $generateHtml
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityDocument $entity,
		$generateHtml = true
	) {
		$parserOutput = new ParserOutput();

		$updaterCollection = new EntityParserOutputDataUpdaterCollection( $parserOutput, $this->dataUpdaters );
		$updaterCollection->updateParserOutput( $entity );

		$configVars = $this->configBuilder->build( $entity );
		$parserOutput->addJsConfigVars( $configVars );
		$parserOutput->setExtensionData( 'wikibase-meta-tags', $this->getMetaTags( $entity ) );

		if ( $generateHtml ) {
			$this->addHtmlToParserOutput(
				$parserOutput,
				$entity,
				$this->getEntityInfo( $parserOutput )
			);
		} else {
			// If we don't have HTML, the ParserOutput in question
			// shouldn't be cacheable.
			$parserOutput->updateCacheExpiry( 0 );
		}

		//@todo: record sitelinks as iwlinks

		$this->addModules( $parserOutput );

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$parserOutput->setTitleText( $entity>getLabel( $langCode ) );

		// Sometimes extensions like SpamBlacklist might call getParserOutput
		// before the id is assigned, during the process of creating a new entity.
		// in that case, no alternate links are added, which probably is no problem.
		$entityId = $entity->getId();
		if ( $entityId !== null ) {
			$this->addAlternateLinks( $parserOutput, $entityId );
		}

		return $parserOutput;
	}

	/**
	 * Fetches some basic entity information from a set of entity IDs.
	 *
	 * @param ParserOutput $parserOutput
	 *
	 * @return EntityInfo
	 */
	private function getEntityInfo( ParserOutput $parserOutput ) {
		/**
		 * Set in ReferencedEntitiesDataUpdater.
		 *
		 * @see ReferencedEntitiesDataUpdater::updateParserOutput
		 * @fixme Use ReferencedEntitiesDataUpdater::getEntityIds instead.
		 */
		$entityIds = $parserOutput->getExtensionData( 'referenced-entities' );

		if ( !is_array( $entityIds ) ) {
			wfLogWarning( '$entityIds from ParserOutput "referenced-entities" extension data'
				. ' expected to be an array' );
			$entityIds = [];
		}

		return $this->entityInfoBuilder->collectEntityInfo( $entityIds, $this->languageFallbackChain->getFetchLanguageCodes() );
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string[]
	 */
	private function getMetaTags( EntityDocument $entity ) {
		$meta = [
			'title' => $this->getTitleText( $entity ),
		];

		if ( $entity instanceof DescriptionsProvider ) {
			$descriptions = $entity->getDescriptions()->toTextArray();
			$preferred = $this->languageFallbackChain->extractPreferredValue( $descriptions );

			if ( is_array( $preferred ) ) {
				$meta['description'] = $preferred['value'];
			}
		}

		return $meta;
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string|null
	 */
	private function getTitleText( EntityDocument $entity ) {
		$titleText = null;

		if ( $entity instanceof LabelsProvider ) {
			$labels = $entity->getLabels()->toTextArray();
			$preferred = $this->languageFallbackChain->extractPreferredValue( $labels );

			if ( is_array( $preferred ) ) {
				$titleText = $preferred['value'];
			}
		}

		if ( !is_string( $titleText ) ) {
			$entityId = $entity->getId();

			if ( $entityId instanceof EntityId ) {
				$titleText = $entityId->getSerialization();
			}
		}

		return $titleText;
	}

	private function addHtmlToParserOutput(
		ParserOutput $parserOutput,
		EntityDocument $entity,
		EntityInfo $entityInfo
	) {
		$entityLookup = new InMemoryEntityLookup();
		if ( $entity->getId() !== null ) {
			$entityLookup->addEntity( $entity );
		}

		$entityLabelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			new EntityRetrievingTermLookup( $entityLookup ),
			$this->languageFallbackChain
		);

		$editSectionGenerator = new ToolbarEditSectionGenerator(
			new RepoSpecialPageLinker(),
			$this->templateFactory,
			$this->textProvider
		);

		$languageDirectionalityLookup = new MediaWikiLanguageDirectionalityLookup();
		$languageNameLookup = new LanguageNameLookup( $this->languageCode );
		$termsListView = new TermsListView(
			TemplateFactory::getDefaultInstance(),
			$languageNameLookup,
			new MediaWikiLocalizedTextProvider( $this->languageCode ),
			$languageDirectionalityLookup
		);

		$textInjector = new TextInjector();
		$entityTermsView = new PlaceholderEmittingEntityTermsView(
			new FallbackHintHtmlTermRenderer(
				$languageDirectionalityLookup,
				$languageNameLookup
			),
			$entityLabelDescriptionLookup,
			$this->templateFactory,
			$editSectionGenerator,
			$this->textProvider,
			$termsListView,
			$textInjector
		);

		$referencedEntitiesLabelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			new EntityInfoTermLookup( $entityInfo ),
			$this->languageFallbackChain
		);

		$entityView = $this->entityViewFactory->newEntityView(
			$entity->getType(),
			$this->languageCode,
			$referencedEntitiesLabelDescriptionLookup,
			$this->languageFallbackChain,
			$editSectionGenerator,
			$entityTermsView
		);

		// Set the display title to display the label together with the item's id
		$titleHtml = $entityView->getTitleHtml( $entity );
		$parserOutput->setTitleText( $titleHtml );

		$html = $entityView->getHtml( $entity );
		$parserOutput->setText( $html );
		$parserOutput->setExtensionData( 'wikibase-view-chunks', $textInjector->getMarkers() );

		$parserOutput->setExtensionData(
			'wikibase-terms-list-items',
			$entityTermsView->getTermsListItems(
				$this->languageCode,
				$entity instanceof LabelsProvider ? $entity->getLabels() : new TermList(),
				$entity instanceof DescriptionsProvider ? $entity->getDescriptions() : new TermList(),
				$entity instanceof AliasesProvider ? $entity->getAliasGroups() : null
			)
		);
	}

	private function addModules( ParserOutput $parserOutput ) {
		// make css available for JavaScript-less browsers
		$parserOutput->addModuleStyles( [
			'wikibase.common',
			'jquery.ui.core.styles',
			'jquery.wikibase.statementview.RankSelector.styles',
			'jquery.wikibase.toolbar.styles',
			'jquery.wikibase.toolbarbutton.styles',
		] );

		// make sure required client-side resources will be loaded
		// FIXME: Separate the JavaScript that is also needed in read-only mode from
		// the JavaScript that is only necessary for editing.
		$parserOutput->addModules( 'wikibase.ui.entityViewInit' );
		$parserOutput->addModules( 'wikibase.entityPage.entityLoaded' );
	}

	/**
	 * Add alternate links as extension data.
	 * OutputPageBeforeHTMLHookHandler will add these to the OutputPage.
	 *
	 * @param ParserOutput $parserOutput
	 * @param EntityId $entityId
	 */
	private function addAlternateLinks( ParserOutput $parserOutput, EntityId $entityId ) {
		$entityDataFormatProvider = $this->entityDataFormatProvider;
		$subPagePrefix = $entityId->getSerialization() . '.';

		$links = [];

		foreach ( $entityDataFormatProvider->getSupportedFormats() as $format ) {
			$ext = $entityDataFormatProvider->getExtension( $format );

			if ( $ext !== null ) {
				$entityDataTitle = SpecialPage::getTitleFor( 'EntityData', $subPagePrefix . $ext );

				$links[] = [
					'rel' => 'alternate',
					'href' => $entityDataTitle->getCanonicalURL(),
					'type' => $entityDataFormatProvider->getMimeType( $format )
				];
			}
		}

		$parserOutput->setExtensionData( 'wikibase-alternate-links', $links );
	}

}
