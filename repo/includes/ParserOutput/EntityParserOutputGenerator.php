<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use ParserOutput;
use SpecialPage;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Term\AliasesProvider;
use Wikibase\DataModel\Term\DescriptionsProvider;
use Wikibase\DataModel\Term\LabelsProvider;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityInfo;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityInfoTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\Repo\MediaWikiLanguageDirectionalityLookup;
use Wikibase\Repo\MediaWikiLocalizedTextProvider;
use Wikibase\Repo\View\RepoSpecialPageLinker;
use Wikibase\View\EmptyEditSectionGenerator;
use Wikibase\View\LocalizedTextProvider;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\TermsListView;
use Wikibase\View\ToolbarEditSectionGenerator;

/**
 * Creates the parser output for an entity.
 *
 * @since 0.5
 *
 * @license GPL-2.0+
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
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

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
	 * @var bool
	 */
	private $editable;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param EntityInfoBuilderFactory $entityInfoBuilderFactory
	 * @param LanguageFallbackChain $languageFallbackChain
	 * @param TemplateFactory $templateFactory
	 * @param LocalizedTextProvider $textProvider
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param ParserOutputDataUpdater[] $dataUpdaters
	 * @param string $languageCode
	 * @param bool $editable
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		ParserOutputJsConfigBuilder $configBuilder,
		EntityTitleLookup $entityTitleLookup,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		LanguageFallbackChain $languageFallbackChain,
		TemplateFactory $templateFactory,
		LocalizedTextProvider $textProvider,
		EntityDataFormatProvider $entityDataFormatProvider,
		array $dataUpdaters,
		$languageCode,
		$editable
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->configBuilder = $configBuilder;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->templateFactory = $templateFactory;
		$this->textProvider = $textProvider;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->dataUpdaters = $dataUpdaters;
		$this->languageCode = $languageCode;
		$this->editable = $editable;
	}

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @since 0.5
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

		$updater = new EntityParserOutputDataUpdater( $parserOutput, $this->dataUpdaters );
		$updater->processEntity( $entity );
		$updater->finish();

		$configVars = $this->configBuilder->build( $entity );
		$parserOutput->addJsConfigVars( $configVars );
		$parserOutput->setExtensionData( 'wikibase-titletext', $this->getTitleText( $entity ) );

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
		if ( $entity->getId() !== null ) {
			$this->addAlternateLinks( $parserOutput, $entity->getId() );
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
			$entityIds = array();
		}

		$entityInfoBuilder = $this->entityInfoBuilderFactory->newEntityInfoBuilder( $entityIds );

		$entityInfoBuilder->resolveRedirects();
		$entityInfoBuilder->removeMissing();

		$entityInfoBuilder->collectTerms(
			array( 'label', 'description' ),
			$this->languageFallbackChain->getFetchLanguageCodes()
		);

		$entityInfoBuilder->collectDataTypes();
		$entityInfoBuilder->retainEntityInfo( $entityIds );

		return $entityInfoBuilder->getEntityInfo();
	}

	/**
	 * @param EntityDocument $entity
	 *
	 * @return string
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

	/**
	 * @param ParserOutput $parserOutput
	 * @param EntityDocument $entity
	 * @param EntityInfo $entityInfo
	 */
	private function addHtmlToParserOutput(
		ParserOutput $parserOutput,
		EntityDocument $entity,
		EntityInfo $entityInfo
	) {
		$labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			new EntityInfoTermLookup( $entityInfo ),
			$this->languageFallbackChain
		);

		$editSectionGenerator = $this->editable ? new ToolbarEditSectionGenerator(
			new RepoSpecialPageLinker(),
			$this->templateFactory,
			$this->textProvider
		) : new EmptyEditSectionGenerator();

		$termsListView = new TermsListView(
			TemplateFactory::getDefaultInstance(),
			new LanguageNameLookup( $this->languageCode ),
			new MediaWikiLocalizedTextProvider( $this->languageCode ),
			new MediaWikiLanguageDirectionalityLookup()
		);

		$textInjector = new TextInjector();
		$entityTermsView = new PlaceholderEmittingEntityTermsView(
			$this->templateFactory,
			$editSectionGenerator,
			$this->textProvider,
			$termsListView,
			$textInjector
		);

		$entityView = $this->entityViewFactory->newEntityView(
			$entity->getType(),
			$this->languageCode,
			$labelDescriptionLookup,
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
				$entity,
				$entity,
				$entity instanceof AliasesProvider ? $entity : null
			)
		);
	}

	/**
	 * @param ParserOutput $parserOutput
	 */
	private function addModules( ParserOutput $parserOutput ) {
		// make css available for JavaScript-less browsers
		$parserOutput->addModuleStyles( array(
			'wikibase.common',
			'jquery.ui.core.styles',
			'jquery.wikibase.statementview.RankSelector.styles',
			'jquery.wikibase.toolbar.styles',
			'jquery.wikibase.toolbarbutton.styles',
		) );

		// make sure required client-side resources will be loaded
		// FIXME: Separate the JavaScript that is also needed in read-only mode from
		// the JavaScript that is only necessary for editing.
		// Then load JavaScript accordingly depending on $editable.
		$parserOutput->addModules( 'wikibase.ui.entityViewInit' );

		// Load mobile styles, which have targets => mobile
		// and will only be loaded on mobile devices
		$parserOutput->addModules( 'wikibase.mobile' );
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

		$links = array();

		foreach ( $entityDataFormatProvider->getSupportedFormats() as $format ) {
			$ext = $entityDataFormatProvider->getExtension( $format );

			if ( $ext !== null ) {
				$entityDataTitle = SpecialPage::getTitleFor( 'EntityData', $subPagePrefix . $ext );

				$links[] = array(
					'rel' => 'alternate',
					'href' => $entityDataTitle->getCanonicalURL(),
					'type' => $entityDataFormatProvider->getMimeType( $format )
				);
			}
		}

		$parserOutput->setExtensionData( 'wikibase-alternate-links', $links );
	}

}
