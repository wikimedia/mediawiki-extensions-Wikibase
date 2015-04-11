<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use Linker;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataTypeSelector;
use Wikibase\Lib\Store\EntityTitleLookup;
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
	 * Max server side caching time in seconds.
	 *
	 * @type integer
	 */
	const CACHE_TTL_IN_SECONDS = 30;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoStore
	 */
	private $propertyInfoStore;

	/**
	 * @var EntityTitleLookup
	 */
	private $entityTitleLookup;

	/**
	 * @var string
	 */
	private $dataType;

	public function __construct() {
		parent::__construct( 'ListProperties' );

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore(),
			WikibaseRepo::getDefaultInstance()->getEntityTitleLookup()
		);
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 */
	public function initServices(
		DataTypeFactory $dataTypeFactory,
		PropertyInfoStore $propertyInfoStore,
		EntityTitleLookup $entityTitleLookup
	) {
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->entityTitleLookup = $entityTitleLookup;
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

		$output = $this->getOutput();
		$output->setSquidMaxage( static::CACHE_TTL_IN_SECONDS );

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
				'',
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

	private function formatRow( PropertyId $propertyId ) {
		$title = $this->entityTitleLookup->getTitleForId( $propertyId );
		return Linker::linkKnown( $title );
	}

}

