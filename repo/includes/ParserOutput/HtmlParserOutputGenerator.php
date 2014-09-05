<?php

namespace Wikibase\Repo\ParserOutput;

use ParserOutput;
use Wikibase\EntityRevision;
use Wikibase\Repo\View\EntityView;

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

	public function __construct( EntityView $entityView ) {
		$this->entityView = $entityView;
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
		$html = $this->entityView->getHtml( $entityRevision, $editable );
		$pout->setText( $html );
		$pout->setExtensionData( 'wikibase-view-chunks', $this->entityView->getPlaceholders() );

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
