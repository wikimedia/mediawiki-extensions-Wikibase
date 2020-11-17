<?php

namespace Wikibase\Repo\Specials;

use Html;
use Wikibase\Lib\DataTypeDefinitions;

/**
 * Page for listing available datatypes.
 *
 * @license GPL-2.0-or-later
 * @author Jens Ohlig
 */
class SpecialListDatatypes extends SpecialWikibasePage {

	/** @var DataTypeDefinitions */
	private $dataTypeDefinitions;

	public function __construct( DataTypeDefinitions $dataTypeDefinitions ) {
		parent::__construct( 'ListDatatypes' );

		$this->dataTypeDefinitions = $dataTypeDefinitions;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->getOutput()->addHTML( $this->msg( 'wikibase-listdatatypes-intro' )->parse() );
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
		// 'wikibase-listdatatypes-wikibase-item-head'
		// 'wikibase-listdatatypes-wikibase-item-body'
		// 'wikibase-listdatatypes-wikibase-property-head'
		// 'wikibase-listdatatypes-wikibase-property-body'

		foreach ( $this->getDataTypeIdsAndValues() as $dataTypeId => $valueType ) {
			$this->getOutput()->addHTML( $this->getHtmlForDataTypeId( $dataTypeId, $valueType ) );
		}

		$this->getOutput()->addHTML( Html::closeElement( 'dl' ) );
	}

	protected function getDataTypeIdsAndValues() {
		return $this->dataTypeDefinitions->getValueTypes();
	}

	protected function getHtmlForDataTypeId( $dataTypeId, $valueType ) {
		$dataTypeBaseKey = 'wikibase-listdatatypes-' . mb_strtolower( $dataTypeId );
		$valueTypeBaseKey = 'wikibase-listdatavaluetypes-';

		return Html::rawElement(
			'dt',
			[ 'id' => $dataTypeId ],
			$this->msg( $dataTypeBaseKey . '-head' )->parse()
			. $this->msg( 'word-separator' )->escaped()
			. $this->msg( 'parentheses-start' )->parse()
			. $this->msg( $valueTypeBaseKey . 'name-' . $valueType )->parse()
			. $this->msg( 'parentheses-end' )->parse()
		)
		. Html::rawElement( 'dd', [],
			$this->msg( $dataTypeBaseKey . '-body' )->parse()
			. Html::rawElement(
				'dd', [],
				$this->msg( $valueTypeBaseKey . 'generalbody', $valueType )->parse()
			)
			. Html::rawElement( 'p', [],
				$this->getLinkRenderer()->makeKnownLink(
					self::getTitleFor( 'ListProperties', $dataTypeId ),
					$this->msg( 'wikibase-listdatatypes-listproperties' )->text()
				)
			)
		);
	}

}
