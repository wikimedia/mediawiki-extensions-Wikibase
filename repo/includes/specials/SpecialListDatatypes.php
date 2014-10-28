<?php

namespace Wikibase\Repo\Specials;

use Html;
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

	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->getOutput()->addHTML( $this->msg( 'wikibase-listdatatypes-intro' ) );
		$this->getOutput()->addHTML( Html::openElement( 'dl' ));

		// some of the datatype descriptions
		// 'wikibase-listdatatypes-wikibase-item-head'
		// 'wikibase-listdatatypes-wikibase-item-body'
		// 'wikibase-listdatatypes-commonsmedia-head'
		// 'wikibase-listdatatypes-commonsmedia-body'
		// 'wikibase-listdatatypes-geo-coordinate-head'
		// 'wikibase-listdatatypes-geo-coordinate-body'
		// 'wikibase-listdatatypes-quantity-head'
		// 'wikibase-listdatatypes-quantity-body'
		// 'wikibase-listdatatypes-monolingualtext-head'
		// 'wikibase-listdatatypes-monolingualtext-body'
		// 'wikibase-listdatatypes-multilingualtext-head'
		// 'wikibase-listdatatypes-multilingualtext-body'
		// 'wikibase-listdatatypes-time-head'
		// 'wikibase-listdatatypes-text-body'

		foreach ( $this->getDataTypeIds() as $dataTypeId ) {
			$this->getOutput()->addHTML( $this->getHtmlForDataTypeId( $dataTypeId ) );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'dl' ));
	}

	protected function getDataTypeIds() {
		return WikibaseRepo::getDefaultInstance()->getDataTypeFactory()->getTypeIds();
	}

	protected function getHtmlForDataTypeId( $dataTypeId ) {
		$baseKey = 'wikibase-listdatatypes-' . mb_strtolower( $dataTypeId );

		return Html::rawElement(
			'dt',
			array(),
			$this->msg( $baseKey . '-head' )->parse() )
				. Html::rawElement( 'dd', array(), $this->msg( $baseKey . '-body' )->parse()
		);
	}

}
