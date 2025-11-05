<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use MediaWiki\Language\Language;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\SpecialPage\SpecialPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\View\EntityDocumentView;
use Wikibase\View\ViewPlaceHolderEmitter;
use Wikibase\View\Wbui2025FeatureFlag;

/**
 * Creates the parser output for an entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FullEntityParserOutputGenerator implements EntityParserOutputGenerator {

	private DispatchingEntityViewFactory $entityViewFactory;

	private DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory;

	private ParserOutputJsConfigBuilder $configBuilder;

	private TermLanguageFallbackChain $termLanguageFallbackChain;

	private EntityDataFormatProvider $entityDataFormatProvider;

	/**
	 * @var EntityParserOutputUpdater[]
	 */
	private array $dataUpdaters;

	private Language $language;

	private bool|string $wbMobile;

	private bool $isMobileView;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param EntityParserOutputUpdater[] $dataUpdaters
	 * @param Language $language
	 * @param bool|string $wbMobile
	 * @param bool $isMobileView
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory,
		ParserOutputJsConfigBuilder $configBuilder,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		EntityDataFormatProvider $entityDataFormatProvider,
		array $dataUpdaters,
		Language $language,
		bool|string $wbMobile,
		bool $isMobileView
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityMetaTagsCreatorFactory = $entityMetaTagsCreatorFactory;
		$this->configBuilder = $configBuilder;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->dataUpdaters = $dataUpdaters;
		$this->language = $language;
		$this->wbMobile = $wbMobile;
		$this->isMobileView = $isMobileView;
	}

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @throws InvalidArgumentException
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		bool $generateHtml = true
	): ParserOutput {
		$entity = $entityRevision->getEntity();

		$parserOutput = new ParserOutput();
		$parserOutput->resetParseStartTime();

		$entityView = $this->createEntityView( $entityRevision );
		$parserOutputOptions = $entityView->getParserOutputOptions();
		foreach ( $parserOutputOptions as $key => $value ) {
			$parserOutput->setExtensionData( $key, $value );
		}

		$updaterCollection = new EntityParserOutputDataUpdaterCollection( $parserOutput, $this->dataUpdaters );
		$updaterCollection->updateParserOutput( $entity );

		$this->configBuilder->build( $entity, $parserOutput );

		$entityMetaTagsCreator = $this->entityMetaTagsCreatorFactory->newEntityMetaTags( $entity->getType(), $this->language );
		$parserOutput->setExtensionData( 'wikibase-meta-tags', $entityMetaTagsCreator->getMetaTags( $entity ) );

		$parserOutput->setLanguage( $this->language );

		if ( $generateHtml ) {
			$this->addHtmlToParserOutput(
				$parserOutput,
				$entityRevision,
				$entityView,
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

	private function createEntityView( EntityRevision $entityRevision ): EntityDocumentView {
		$entity = $entityRevision->getEntity();

		return $this->entityViewFactory->newEntityView(
			$this->language,
			$this->termLanguageFallbackChain,
			$entity,
			[ Wbui2025FeatureFlag::EXTENSION_DATA_KEY => $this->wbMobile ],
		);
	}

	private function addHtmlToParserOutput(
		ParserOutput $parserOutput,
		EntityRevision $entityRevision,
		EntityDocumentView $entityView,
	): void {
		$entity = $entityRevision->getEntity();

		// Set the display title to display the label together with the item's id
		$titleHtml = $entityView->getTitleHtml( $entity );
		$parserOutput->setTitleText( $titleHtml ?? '' );

		// split parser cache by desktop/mobile/wbui2025 (T344362, T394291, T394291)
		$parserOutput->recordOption( Wbui2025FeatureFlag::PARSER_OPTION_NAME );
		$viewContent = $entityView->getContent( $entity, $entityRevision->getRevisionId() );
		$parserOutput->setContentHolderText( $viewContent->getHtml() );

		$placeholders = $viewContent->getPlaceholders();
		foreach ( $placeholders as $key => $value ) {
			if ( $value === ViewPlaceHolderEmitter::ERRONEOUS_PLACEHOLDER_VALUE ) {
				$parserOutput->updateCacheExpiry( 0 );
				continue;
			}

			$parserOutput->setExtensionData( $key, $value );
		}
	}

	private function addModules( ParserOutput $parserOutput ): void {
		// make css available for JavaScript-less browsers
		$parserOutput->addModuleStyles( [
			'wikibase.alltargets',
		] );
		// split parser cache by desktop/mobile (T344362)
		$parserOutput->recordOption( Wbui2025FeatureFlag::PARSER_OPTION_NAME );
		// T324991
		if ( !$this->isMobileView ) {
			$parserOutput->addModuleStyles( [
				'wikibase.desktop',
				'jquery.wikibase.toolbar.styles',
			] );
		}

		$parserOutput->addModules( [
			// fire the entityLoaded hook which got configured through $this->configBuilder
			'wikibase.entityPage.entityLoaded',
		] );
		// T324991
		if ( !$this->isMobileView ) {
			// make sure required client-side resources will be loaded
			// FIXME: Separate the JavaScript that is also needed in read-only mode from
			// the JavaScript that is only necessary for editing.
			$parserOutput->addModules( [
				'wikibase.ui.entityViewInit',
			] );
		}
	}

	/**
	 * Add alternate links as extension data.
	 * OutputPageBeforeHTMLHookHandler will add these to the OutputPage.
	 */
	private function addAlternateLinks( ParserOutput $parserOutput, EntityId $entityId ): void {
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
					'type' => $entityDataFormatProvider->getMimeType( $format ),
				];
			}
		}

		$parserOutput->setExtensionData( 'wikibase-alternate-links', $links );
	}

}
