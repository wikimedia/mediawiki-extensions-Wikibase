<?php

namespace Wikibase\Repo\ParserOutput;

use InvalidArgumentException;
use Language;
use ParserOutput;
use SpecialPage;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\TermLanguageFallbackChain;
use Wikibase\Repo\LinkedData\EntityDataFormatProvider;
use Wikibase\View\ViewPlaceHolderEmitter;

/**
 * Creates the parser output for an entity.
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class FullEntityParserOutputGenerator implements EntityParserOutputGenerator {

	/**
	 * @var DispatchingEntityViewFactory
	 */
	private $entityViewFactory;

	/**
	 * @var DispatchingEntityMetaTagsCreatorFactory
	 */
	private $entityMetaTagsCreatorFactory;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	private $configBuilder;

	/**
	 * @var TermLanguageFallbackChain
	 */
	private $termLanguageFallbackChain;

	/**
	 * @var EntityDataFormatProvider
	 */
	private $entityDataFormatProvider;

	/**
	 * @var EntityParserOutputUpdater[]
	 */
	private $dataUpdaters;

	/**
	 * @var Language
	 */
	private $language;

	/**
	 * @param DispatchingEntityViewFactory $entityViewFactory
	 * @param DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 * @param TermLanguageFallbackChain $termLanguageFallbackChain
	 * @param EntityDataFormatProvider $entityDataFormatProvider
	 * @param EntityParserOutputUpdater[] $dataUpdaters
	 * @param Language $language
	 */
	public function __construct(
		DispatchingEntityViewFactory $entityViewFactory,
		DispatchingEntityMetaTagsCreatorFactory $entityMetaTagsCreatorFactory,
		ParserOutputJsConfigBuilder $configBuilder,
		TermLanguageFallbackChain $termLanguageFallbackChain,
		EntityDataFormatProvider $entityDataFormatProvider,
		array $dataUpdaters,
		Language $language
	) {
		$this->entityViewFactory = $entityViewFactory;
		$this->entityMetaTagsCreatorFactory = $entityMetaTagsCreatorFactory;
		$this->configBuilder = $configBuilder;
		$this->termLanguageFallbackChain = $termLanguageFallbackChain;
		$this->entityDataFormatProvider = $entityDataFormatProvider;
		$this->dataUpdaters = $dataUpdaters;
		$this->language = $language;
	}

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $generateHtml
	 *
	 * @throws InvalidArgumentException
	 * @return ParserOutput
	 */
	public function getParserOutput(
		EntityRevision $entityRevision,
		$generateHtml = true
	) {
		$entity = $entityRevision->getEntity();

		$parserOutput = new ParserOutput();

		$updaterCollection = new EntityParserOutputDataUpdaterCollection( $parserOutput, $this->dataUpdaters );
		$updaterCollection->updateParserOutput( $entity );

		$configVars = $this->configBuilder->build( $entity );
		$parserOutput->addJsConfigVars( $configVars );

		$entityMetaTagsCreator = $this->entityMetaTagsCreatorFactory->newEntityMetaTags( $entity->getType(), $this->language );

		$parserOutput->setExtensionData( 'wikibase-meta-tags', $entityMetaTagsCreator->getMetaTags( $entity ) );

		if ( $generateHtml ) {
			$this->addHtmlToParserOutput(
				$parserOutput,
				$entityRevision
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

	private function addHtmlToParserOutput(
		ParserOutput $parserOutput,
		EntityRevision $entityRevision
	) {
		$entity = $entityRevision->getEntity();

		$entityView = $this->entityViewFactory->newEntityView(
			$this->language,
			$this->termLanguageFallbackChain,
			$entity
		);

		// Set the display title to display the label together with the item's id
		$titleHtml = $entityView->getTitleHtml( $entity );
		$parserOutput->setTitleText( $titleHtml );

		$viewContent = $entityView->getContent( $entity, $entityRevision->getRevisionId() );
		$parserOutput->setText( $viewContent->getHtml() );

		$placeholders = $viewContent->getPlaceholders();
		foreach ( $placeholders as $key => $value ) {
			if ( $value === ViewPlaceHolderEmitter::ERRONEOUS_PLACEHOLDER_VALUE ) {
				$parserOutput->updateCacheExpiry( 0 );
				continue;
			}

			$parserOutput->setExtensionData( $key, $value );
		}
	}

	private function addModules( ParserOutput $parserOutput ) {
		// make css available for JavaScript-less browsers
		$parserOutput->addModuleStyles( [
			'wikibase.alltargets',
			'wikibase.desktop',
			'jquery.wikibase.toolbar.styles',
		] );

		$parserOutput->addModules( [
			// fire the entityLoaded hook which got configured through $this->configBuilder
			'wikibase.entityPage.entityLoaded',
			// make sure required client-side resources will be loaded
			// FIXME: Separate the JavaScript that is also needed in read-only mode from
			// the JavaScript that is only necessary for editing.
			'wikibase.ui.entityViewInit',
		] );
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
					'type' => $entityDataFormatProvider->getMimeType( $format ),
				];
			}
		}

		$parserOutput->setExtensionData( 'wikibase-alternate-links', $links );
	}

}
