<?php

namespace Wikibase;

use ParserOutput;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\Lib\Store\EntityTitleLookup;

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

	public function __construct(
		EntityView $entityView,
		ParserOutputJsConfigBuilder $configBuilder,
		SerializationOptions $serializationOptions,
		EntityTitleLookup $entityTitleLookup,
		PropertyDataTypeLookup $dataTypeLookup
	) {
		$this->entityView = $entityView;
		$this->configBuilder = $configBuilder;
		$this->serializationOptions = $serializationOptions;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->dataTypeLookup = $dataTypeLookup;
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

		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$configVars = $this->configBuilder->build( $entity, $this->serializationOptions, $isExperimental );
		$pout->addJsConfigVars( $configVars );

		$this->addSnaksToParserOutput( $pout, $entity->getAllSnaks() );

		if ( $entity instanceof Item ) {
			$this->addBadgesToParserOutput( $pout, $entity->getSiteLinkList() );
		}

		if ( $generateHtml ) {
			$this->addHtmlToParserOutput( $pout, $entityRevision, $editable );
		}

		//@todo: record sitelinks as iwlinks
		//@todo: record CommonsMedia values as imagelinks

		$this->addModules( $pout );

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		return $pout;
	}

	private function addSnaksToParserOutput( ParserOutput $pout, array $snaks ) {
		// treat referenced entities as page links ------
		$entitiesFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $entitiesFinder->findSnakLinks( $snaks );

		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		$valuesFinder = new ValuesFinder( $this->dataTypeLookup );

		// treat URL values as external links ------
		$usedUrls = $valuesFinder->findFromSnaks( $snaks, 'url' );

		foreach ( $usedUrls as $url ) {
			$pout->addExternalLink( $url->getValue() );
		}

		// treat CommonsMedia values as file transclusions ------
		$usedImages = $valuesFinder->findFromSnaks( $snaks, 'commonsMedia' );

		foreach( $usedImages as $image ) {
			$pout->addImage( str_replace( ' ', '_', $image->getValue() ) );
		}
	}

	private function addBadgesToParserOutput( ParserOutput $pout, SiteLinkList $siteLinkList ) {
		foreach ( $siteLinkList as $siteLink ) {
			foreach ( $siteLink->getBadges() as $badge ) {
				$pout->addLink( $this->entityTitleLookup->getTitleForId( $badge ) );
			}
		}
	}

	private function addHtmlToParserOutput( ParserOutput $pout, EntityRevision $entityRevision, $editable ) {
		$html = $this->entityView->getHtml( $entityRevision, $editable );
		$pout->setText( $html );
		$pout->setExtensionData( 'wikibase-view-chunks', $this->entityView->getPlaceholders() );
	}

	private function addModules( ParserOutput $pout ) {
		// make css available for JavaScript-less browsers
		$pout->addModuleStyles( array(
			'wikibase.common',
			'wikibase.toc',
			'jquery.ui.core',
			'jquery.wikibase.statementview',
			'jquery.wikibase.toolbar',
		) );

		// make sure required client sided resources will be loaded:
		$pout->addModules( 'wikibase.ui.entityViewInit' );
	}

}
