<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use Linker;
use MWException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataTypeSelector;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page to list properties by data type
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class SpecialListProperties extends SpecialWikibasePage {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @since 0.5
	 */
	public function __construct() {
		parent::__construct( 'ListProperties' );

		$this->dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$this->propertyInfoStore = WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.5
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$this->prepareArguments( $subPage );
		$this->showForm();

		if ( $this->dataType !== null ) {
			$this->showQuery();
		}
	}

	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();

		$this->dataType = $request->getText( 'datatype', $subPage );
		if ( !in_array( $this->dataType, $this->dataTypeFactory->getTypeIds() ) ) {
			$this->dataType = null;
		}
	}

	private function showForm() {
		$dataTypeSelect = new DataTypeSelector(
			$this->dataTypeFactory->getTypes(),
			$this->getLanguage()->getCode()
		);

		$this->getOutput()->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $this->getPageTitle()->getLocalURL(),
					'name' => 'listproperties',
					'id' => 'wb-listproperties-form'
				)
			) .
			Html::input (
				'title',
				$this->getPageTitle()->getPrefixedText(),
				'hidden',
				array()
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				$this->msg( 'wikibase-listproperties-legend' )->text()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-listproperties-datatype'
				),
				$this->msg( 'wikibase-listproperties-label-datatype' )->text()
			) . ' ' .
			$dataTypeSelect->getHTML( 'wb-listproperties-datatype', 'datatype', $this->dataType ) . ' ' .
			Html::input(
				'submit',
				$this->msg( 'wikibase-listproperties-submit' )->text(),
				'submit',
				array(
					'id' => 'wikibase-listproperties-submit',
					'class' => 'wb-input-button'
				)
			) .
			Html::closeElement( 'p' ) .
			Html::closeElement( 'fieldset' ) .
			Html::closeElement( 'form' )
		);
	}

	private function showQuery() {
		$propertyInfoForDataType = $this->propertyInfoStore->getPropertyInfoForDataType( $this->dataType );

		if ( empty( $propertyInfoForDataType ) ) {
			$this->getOutput()->addWikiMsg( 'specialpage-empty' );
			return;
		}

		$html = Html::openElement( 'ul' );

		foreach ( $propertyInfoForDataType as $numericId => $info ) {
			$row = $this->formatRow( PropertyId::newFromNumber( $numericId ) );
			$html .= Html::rawElement( 'li', array(), $row );
		}

		$html .= Html::closeElement( 'ul' );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @see SpecialWikibaseQueryPage::formatRow
	 */
	private function formatRow( PropertyId $propertyId ) {
		try {
			$title = WikibaseRepo::getDefaultInstance()->getEntityContentFactory()->getTitleForId( $propertyId );
			return Linker::linkKnown( $title );
		} catch ( MWException $e ) {
			wfWarn( "Error formatting result row: " . $e->getMessage() );
			return false;
		}
	}

}

