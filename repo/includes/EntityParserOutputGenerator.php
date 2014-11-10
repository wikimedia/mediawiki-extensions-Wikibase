<?php

namespace Wikibase;

use Language;
use ParserOutput;
use ValueFormatters\FormatterOptions;
use ValueFormatters\ValueFormatter;
use Wikibase\DataModel\Entity\Entity;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyDataTypeLookup;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\LanguageFallbackChain;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\OutputFormatSnakFormatterFactory;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\EntityInfoBuilderFactory;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Lib\Store\EntityRetrievingTermLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageLabelLookup;
use Wikibase\Lib\WikibaseValueFormatterBuilders;

/**
 * Creates the parser output for an entity.
 *
 * @note This class relies on Entity and behaves differently when you pass an item as paramater.
 *		 We should split this into classes for items and other types of entities.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutputGenerator {

	/**
	 * @var EntityView
	 */
	private $entityView;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	private $configBuilder;

	/**
	 * @var SerializationOptions
	 */
	private $serializationOptions;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	/**
	 * @var EntityInfoBuilderFactory
	 */
	private $entityInfoBuilderFactory;

	/**
	 * @var OutputFormatSnakFormatterFactory
	 */
	private $snakFormatterFactory;

	/**
	 * @var LanguageFallbackChain
	 */
	private $languageFallbackChain;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string
	 */
	private $languageCode;

	/**
	 * @var ReferencedEntitiesFinder
	 */
	private $referencedEntitiesFinder;

	public function __construct(
		EntityView $entityView,
		ParserOutputJsConfigBuilder $configBuilder,
		SerializationOptions $serializationOptions,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityInfoBuilderFactory $entityInfoBuilderFactory,
		WikibaseValueFormatterBuilders $valueFormatterBuilders,
		OutputFormatSnakFormatterFactory $snakFormatterFactory,
		LanguageFallbackChain $languageFallbackChain,
		EntityLookup $entityLookup,
		$languageCode
	) {
		$this->entityView = $entityView;
		$this->configBuilder = $configBuilder;
		$this->serializationOptions = $serializationOptions;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityInfoBuilderFactory = $entityInfoBuilderFactory;
		$this->valueFormatterBuilders = $valueFormatterBuilders;
		$this->snakFormatterFactory = $snakFormatterFactory;
		$this->languageFallbackChain = $languageFallbackChain;
		$this->languageCode = $languageCode;
		$this->entityLookup = $entityLookup;

		$this->referencedEntitiesFinder = new ReferencedEntitiesFinder();
	}

	/**
	 * Creates the parser output for the given entity revision.
	 *
	 * @since 0.5
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 * @param bool $generateHtml
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, $editable = true, $generateHtml = true ) {
		$pout = new ParserOutput();

		$entity =  $entityRevision->getEntity();
		$snaks = $entity->getAllSnaks();

		$referencedEntityIds = $this->referencedEntitiesFinder->findSnakLinks( $snaks );
		$entityInfo = $this->getEntityInfo( $referencedEntityIds );

		$configVars = $this->configBuilder->build(
			$entity,
			$entityInfo,
			$this->serializationOptions
		);

		$pout->addJsConfigVars( $configVars );

		$this->addSnaksToParserOutput( $pout, $referencedEntityIds, $snaks );

		if ( $entity instanceof Item ) {
			$this->addBadgesToParserOutput( $pout, $entity->getSiteLinkList() );
		}

		if ( $generateHtml ) {
			$this->addHtmlToParserOutput( $pout, $entityRevision, $editable );
		}

		//@todo: record sitelinks as iwlinks
		//@todo: record CommonsMedia values as imagelinks

		$this->addModules( $pout, $editable );

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		return $pout;
	}

	private function addSnaksToParserOutput( ParserOutput $pout, array $usedEntityIds, array $snaks ) {
		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		$valuesFinder = new ValuesFinder( $this->dataTypeLookup );

		// treat URL values as external links ------
		$usedUrls = $valuesFinder->findFromSnaks( $snaks, 'url' );

		foreach ( $usedUrls as $url ) {
			$value = $url->getValue();
			if ( is_string( $value ) ) {
				$pout->addExternalLink( $value );
			}
		}

		// treat CommonsMedia values as file transclusions ------
		$usedImages = $valuesFinder->findFromSnaks( $snaks, 'commonsMedia' );

		foreach ( $usedImages as $image ) {
			$value = $image->getValue();
			if ( is_string( $value ) ) {
				$pout->addImage( str_replace( ' ', '_', $value ) );
			}
		}
	}

	/**
	 * Fetches some basic entity information required for the entity view in JavaScript from a
	 * set of entity IDs.
	 * @since 0.4
	 *
	 * @param EntityId[] $entityIds
	 * @return array obtained from EntityInfoBuilder::getEntityInfo
	 */
	private function getEntityInfo( array $entityIds ) {
		wfProfileIn( __METHOD__ );

		// TODO: apply language fallback!
		$entityInfoBuilder = $this->entityInfoBuilderFactory->newEntityInfoBuilder( $entityIds );

		$entityInfoBuilder->resolveRedirects();
		$entityInfoBuilder->removeMissing();

		$entityInfoBuilder->collectTerms(
			array( 'label', 'description' ),
			array( $this->languageCode )
		);

		$entityInfoBuilder->collectDataTypes();
		$entityInfoBuilder->retainEntityInfo( $entityIds );

		$entityInfo = $entityInfoBuilder->getEntityInfo();

		wfProfileOut( __METHOD__ );
		return $entityInfo;
	}

	private function addBadgesToParserOutput( ParserOutput $pout, SiteLinkList $siteLinkList ) {
		foreach ( $siteLinkList as $siteLink ) {
			foreach ( $siteLink->getBadges() as $badge ) {
				$pout->addLink( $this->entityTitleLookup->getTitleForId( $badge ) );
			}
		}
	}

	private function addHtmlToParserOutput( ParserOutput $pout, EntityRevision $entityRevision, $editable ) {
		$snakFormatter = $this->getSnakFormatter();
		$html = $this->entityView->getHtml( $entityRevision, $snakFormatter, $editable );
		$pout->setText( $html );
		$pout->setExtensionData( 'wikibase-view-chunks', $this->entityView->getPlaceholders() );
	}

	private function addModules( ParserOutput $pout, $editable ) {
		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array(
			'wikibase.common',
			'wikibase.toc',
			'jquery.ui.core',
			'jquery.wikibase.statementview',
			'jquery.wikibase.toolbar',
		) );

		if ( $editable ) {
			// make sure required client sided resources will be loaded:
			$pout->addModules( 'wikibase.ui.entityViewInit' );
		}
	}

	/**
	 * @return SnakFormatter
	 */
	private function getSnakFormatter() {
		$formatterOptions = new FormatterOptions();
		$formatterOptions->setOption( ValueFormatter::OPT_LANG, $this->languageCode );
		$formatterOptions->setOption( 'languages', $this->languageFallbackChain );

		// @fixme use language fallback here
		return $this->snakFormatterFactory->getSnakFormatter(
			SnakFormatter::FORMAT_HTML_WIDGET,
			$this->getValueFormatterBuilders( $this->languageCode ),
			$formatterOptions
		);
	}

	/**
	 * @return WikibaseValueFormatterBuilders
	 */
	private function getValueFormatterBuilders() {
		$termLookup = new EntityRetrievingTermLookup( $this->entityLookup );

		return new WikibaseValueFormatterBuilders(
			$this->entityLookup,
			Language::factory( $this->languageCode ),
			new LanguageLabelLookup( $termLookup, $this->languageCode ),
			$this->entityTitleLookup
		);
	}

}
