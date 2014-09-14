<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\EntityRevision;
use Wikibase\EntityView;
use Wikibase\Lib\Serializers\SerializationOptions;
use Wikibase\ParserOutputJsConfigBuilder;

/**
 * Adds Html to the parser output from an entity view.
 *
 * @since 0.5
 *
 * @license GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class HtmlParserOutputGenerator {

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

	public function __construct(
		EntityView $entityView,
		ParserOutputJsConfigBuilder $configBuilder,
		SerializationOptions $serializationOptions
	) {
		$this->entityView = $entityView;
		$this->configBuilder = $configBuilder;
		$this->serializationOptions = $serializationOptions;
	}

	/**
	 * Assigns information about the given entity revision to the parser output.
	 *
	 * @since 0.5
	 *
	 * @param ParserOutput $pout
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 */
	public function assignToParserOutput( ParserOutput $pout, EntityRevision $entityRevision, $editable ) {
		$this->addConfigVarsToParserOutput( $pout, $entityRevision->getEntity() );
		$this->addHtmlToParserOutput( $pout, $entityRevision, $editable );
		$this->addModulesToParserOutput( $pout );
	}

	private function addConfigVarsToParserOutput( ParserOutput $pout, Entity $entity ) {
		$isExperimental = defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES;
		$configVars = $this->configBuilder->build( $entity, $this->serializationOptions, $isExperimental );
		$pout->addJsConfigVars( $configVars );
	}

	private function addHtmlToParserOutput( ParserOutput $pout, EntityRevision $entityRevision, $editable ) {
		$html = $this->entityView->getHtml( $entityRevision, $editable );
		$pout->setText( $html );
		$pout->setExtensionData( 'wikibase-view-chunks', $this->entityView->getPlaceholders() );
	}

	private function addModulesToParserOutput( ParserOutput $pout ) {
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
