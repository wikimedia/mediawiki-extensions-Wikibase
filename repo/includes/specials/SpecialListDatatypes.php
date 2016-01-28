<?php

namespace Wikibase\Repo\Specials;

use Html;
use Linker;
use SpecialPageFactory;
use Title;
use Wikibase\Repo\WikibaseRepo;

/**
 * Page for listing available datatypes.
 *
 * @since 0.2
 * @licence GNU GPL v2+
 * @author Jens Ohlig
 */
class SpecialListDatatypes extends SpecialWikibasePage {

	/**
	 * @since 0.2
	 */
	public function __construct() {
		parent::__construct( 'ListDatatypes' );
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		// in fact, this is just a dummy
		$this->addHelpLink( '//www.wikidata.org/wiki/Special:MyLanguage/Help:Data_type', true );

		$this->getOutput()->addHTML( $this->msg( 'wikibase-listdatatypes-intro' ) );
		$this->getOutput()->addHTML( Html::openElement( 'dl' ) );

		// some of the datatype descriptions
		// 'wikibase-listdatatypes-wikibase-item-head'
		// 'wikibase-listdatatypes-wikibase-item-body'
		// 'wikibase-listdatatypes-wikibase-property-head'
		// 'wikibase-listdatatypes-wikibase-property-body'
		// 'wikibase-listdatatypes-commonsmedia-head'
		// 'wikibase-listdatatypes-commonsmedia-body'
		// 'wikibase-listdatatypes-globe-coordinate-head'
		// 'wikibase-listdatatypes-globe-coordinate-body'
		// 'wikibase-listdatatypes-quantity-head'
		// 'wikibase-listdatatypes-quantity-body'
		// 'wikibase-listdatatypes-monolingualtext-head'
		// 'wikibase-listdatatypes-monolingualtext-body'
		// 'wikibase-listdatatypes-multilingualtext-head'
		// 'wikibase-listdatatypes-multilingualtext-body'
		// 'wikibase-listdatatypes-time-head'
		// 'wikibase-listdatatypes-time-body'
		// 'wikibase-listdatatypes-string-head'
		// 'wikibase-listdatatypes-string-body'
		// 'wikibase-listdatatypes-url-head'
		// 'wikibase-listdatatypes-url-body'
		// 'wikibase-listdatatypes-external-id-head'
		// 'wikibase-listdatatypes-external-id-body'

		foreach ( $this->getDataTypeIds() as $dataTypeId ) {
			$this->getOutput()->addHTML( $this->getHtmlForDataTypeId( $dataTypeId ) );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'dl' ) );
	}

	protected function getDataTypeIds() {
		return WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getTypeIds();
	}

	protected function getHtmlForDataTypeId( $dataTypeId ) {
		$baseKey = 'wikibase-listdatatypes-' . mb_strtolower( $dataTypeId );

		return Html::rawElement(
			'dt',
			array(),
			$this->msg( $baseKey . '-head' )->parse()
		)
		. Html::rawElement( 'dd', array(),
			$this->msg( $baseKey . '-body' )->parse()
			. Html::rawElement( 'p', array(),
				Linker::linkKnown( Title::newFromText( 'Special:' . SpecialPageFactory::getLocalNameFor( 'ListProperties', $dataTypeId ) ),
					$this->msg( 'wikibase-listdatatypes-listproperties' )->parse() )
			)
		);
	}

}
