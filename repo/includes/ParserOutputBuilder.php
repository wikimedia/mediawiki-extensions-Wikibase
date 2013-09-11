<?php

namespace Wikibase;

use ParserOutput;
use Language;
use IContextSource;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\Lib\Serializers\SerializationOptions;

/**
 * @since 0.6
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki at snater.com >
 * @author Daniel Werner
 * @author Daniel Kinzler
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class ParserOutputBuilder {

	/**
	 * @var EntityTitleLookup
	 */
	protected $entityTitleLookup;

	/**
	 * @var PropertyDataTypeLookup
	 */
	protected $dataTypeLookup;

	/**
	 * @var TextInjector
	 */
	protected $injector;

	/**
	 * @var ParserOutputJsConfigBuilder
	 */
	protected $configBuilder;

	/**
	 * @param IContextSource|null $context
	 * @param PropertyDataTypeLookup $dataTypeLookup
	 * @param EntityTitleLookup $entityTitleLookup
	 * @param SerializationOptions $options
	 * @param ParserOutputJsConfigBuilder $configBuilder
	 */
	public function __construct(
		IContextSource $context,
		PropertyDataTypeLookup $dataTypeLookup,
		EntityTitleLookup $entityTitleLookup,
		SerializationOptions $options,
		ParserOutputJsConfigBuilder $configBuilder
	) {
		$this->context = $context;
		$this->dataTypeLookup = $dataTypeLookup;
		$this->entityTitleLookup = $entityTitleLookup;
		$this->options = $options;
		$this->configBuilder = $configBuilder;

		$this->injector = new TextInjector();
	}

	/**
	 * Renders an entity into an ParserOutput object
	 *
	 * @param EntityRevision $entityRevision the entity to analyze/render
	 * @param string $html HTML content to put into parser output (optional)
	 *
	 * @return ParserOutput
	 */
	public function getParserOutput( EntityRevision $entityRevision, $html = null ) {
		wfProfileIn( __METHOD__ );

		// fresh parser output with entity markup
		$pout = new ParserOutput();

		$entity =  $entityRevision->getEntity();
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;

		$configVars = $this->configBuilder->build( $entity, $this->options, $isExperimental );
		$pout->addJsConfigVars( $configVars );

		$allSnaks = $entityRevision->getEntity()->getAllSnaks();

		// treat referenced entities as page links ------
		$refFinder = new ReferencedEntitiesFinder();
		$usedEntityIds = $refFinder->findSnakLinks( $allSnaks );

		foreach ( $usedEntityIds as $entityId ) {
			$pout->addLink( $this->entityTitleLookup->getTitleForId( $entityId ) );
		}

		// treat URL values as external links ------
		$urlFinder = new ReferencedUrlFinder( $this->dataTypeLookup );
		$usedUrls = $urlFinder->findSnakLinks( $allSnaks );

		foreach ( $usedUrls as $url ) {
			$pout->addExternalLink( $url );
		}

		if ( $html ) {
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
		//	   But we still want to use OutputPage::addParserOutput to apply the modules etc from the ParserOutput.
		//	   So, for now, we leave it to the caller to override the display title, if desired.
		// set the display title
		//$pout->setTitleText( $entity>getLabel( $langCode ) );

		wfProfileOut( __METHOD__ );
		return $pout;
	}

	/**
	 * Returns the placeholder map build while generating HTML.
	 * The map returned here may be used with TextInjector.
	 *
	 * @return array string -> array
	 */
	public function getPlaceholders() {
		return $this->injector->getMarkers();
	}

}
