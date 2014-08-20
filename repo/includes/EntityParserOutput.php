<?php

namespace Wikibase;

/**
 * Factory to render an entity to the parser output.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class EntityParserOutput extends \ParserOutput {

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	private $configBuilder;

	/**
	 * @var SerializationOptions
	 */
	private $options;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	private $dataTypeLookup;

	public function addEntityConfigVars( Entity $entity ) {
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$configVars = $this->configBuilder->build( $entity, $this->options, $isExperimental );
		$this->addJsConfigVars( $configVars );
	}

	/**
	 * 
	 * @param Snak[] $snaks
	 */
	public function addSnakLinks( array $snaks ) {
		// treat referenced entities as page links ------
		$refFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $refFinder->findSnakLinks( $snaks );

		foreach ( $usedEntityIds as $entityId ) {
			$this->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		// treat URL values as external links ------
		$urlFinder = new ReferencedUrlFinder( $this->dataTypeLookup );
		$usedUrls = $urlFinder->findSnakLinks( $snaks );

		foreach ( $usedUrls as $url ) {
			$this->addExternalLink( $url );
		}
	}

	/**
	 * Renders an entity into an ParserOutput object
	 *
	 * @since 0.5
	 *
	 * @param EntityRevision $entityRevision the entity to analyze/render
	 * @param bool $editable whether to make the page's content editable
	 * @param bool $generateHtml whether to generate HTML. Set to false if only interested in meta-info. default: true.
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, $editable = true,
		$generateHtml = true
	) {
		wfProfileIn( __METHOD__ );

		// fresh parser output with entity markup
		$pout = new ParserOutput();

		$entity =  $entityRevision->getEntity();
		$allSnaks = $entityRevision->getEntity()->getAllSnaks();


		if ( $generateHtml ) {
			$html = $this->getHtml( $entityRevision, $editable );
			$pout->setText( $html );
			$pout->setExtensionData( 'wikibase-view-chunks', $this->getPlaceholders() );
		}

		//@todo: record sitelinks as iwlinks
		//@todo: record CommonsMedia values as imagelinks

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

		//FIXME: some places, like Special:NewItem, don't want to override the page title.
		//	 But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	 So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		wfProfileOut( __METHOD__ );
		return $pout;
	}

}
