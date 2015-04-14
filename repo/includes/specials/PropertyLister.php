<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use Linker;
use MWException;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataTypeSelector;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\WikibaseRepo;

/**
 * Special page to list properties by data type
 *
 * @since 0.5
 * @licence GNU GPL v2+
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyLister {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var EntityContentFactory
	 */
	private $entityContentFactory;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @since 0.5
	 */
	public function __construct() {
		$this->dataTypeFactory = WikibaseRepo::getDefaultInstance()->getDataTypeFactory();
		$this->propertyInfoStore = WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore();
		$this->entityContentFactory = WikibaseRepo::getDefaultInstance()->getEntityContentFactory();
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @since 0.5
	 *
	 * @param string|null $subPage
	 */
	public function doExecute( &$output, $request, $title, $languageCode, $subPage ) {
		$this->prepareArguments( $request, $subPage );
		$this->showForm( $output, $title, $languageCode );

		if ( $this->dataType !== null ) {
			$this->showQuery( $output );
		}
	}

	private function prepareArguments( $request, $subPage ) {
		$this->dataType = $request->getText( 'datatype', $subPage );
		if ( !in_array( $this->dataType, $this->dataTypeFactory->getTypeIds() ) ) {
			$this->dataType = null;
		}
	}

	private function showForm( &$output, $title, $languageCode ) {
		$dataTypeSelect = new DataTypeSelector(
			$this->dataTypeFactory->getTypes(),
			$languageCode
		);

		$output->addHTML(
			Html::openElement(
				'form',
				array(
					'action' => $title->getLocalURL(),
					'name' => 'listproperties',
					'id' => 'wb-listproperties-form'
				)
			) .
			Html::input (
				'title',
				$title->getPrefixedText(),
				'hidden',
				array()
			) .
			Html::openElement( 'fieldset' ) .
			Html::element(
				'legend',
				array(),
				wfMessage( 'wikibase-listproperties-legend' )->text()
			) .
			Html::openElement( 'p' ) .
			Html::element(
				'label',
				array(
					'for' => 'wb-listproperties-datatype'
				),
				wfMessage( 'wikibase-listproperties-label-datatype' )->text()
			) . ' ' .
			$dataTypeSelect->getHTML( 'wb-listproperties-datatype', 'datatype', $this->dataType ) . ' ' .
			Html::input(
				'submit',
				wfMessage( 'wikibase-listproperties-submit' )->text(),
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

	private function showQuery( &$output ) {
		$propertyInfoForDataType = $this->propertyInfoStore->getPropertyInfoForDataType( $this->dataType );

		if ( empty( $propertyInfoForDataType ) ) {
			$output->addWikiMsg( 'specialpage-empty' );
			return;
		}

		$html = Html::openElement( 'ul' );

		foreach ( $propertyInfoForDataType as $numericId => $info ) {
			$row = $this->formatRow( PropertyId::newFromNumber( $numericId ) );
			$html .= Html::rawElement( 'li', array(), $row );
		}

		$html .= Html::closeElement( 'ul' );
		$output->addHTML( $html );
	}

	private function formatRow( PropertyId $propertyId ) {
		$title = $this->entityContentFactory->getTitleForId( $propertyId );
		return Linker::linkKnown( $title );
	}

}

