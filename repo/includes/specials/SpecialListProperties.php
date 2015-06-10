<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataTypeSelector;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\View\EntityIdFormatterFactory;

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
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/**
	 * @var LanguageFallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var string
	 */
	private $dataType;

	public function __construct() {
		parent::__construct( 'ListProperties' );

		$this->initServices(
			WikibaseRepo::getDefaultInstance()->getDataTypeFactory(),
			WikibaseRepo::getDefaultInstance()->getStore()->getPropertyInfoStore(),
			WikibaseRepo::getDefaultInstance()->getEntityIdHtmlLinkFormatterFactory(),
			new LanguageFallbackLabelDescriptionLookupFactory(
				WikibaseRepo::getDefaultInstance()->getLanguageFallbackChainFactory(),
				WikibaseRepo::getDefaultInstance()->getTermLookup(),
				WikibaseRepo::getDefaultInstance()->getTermBuffer()
			)
		);
	}

	/**
	 * Set service objects to use. Unit tests may call this to substitute mock
	 * services.
	 */
	public function initServices(
		DataTypeFactory $dataTypeFactory,
		PropertyInfoStore $propertyInfoStore,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory
	) {
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
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
		if ( $this->dataType !== '' && !in_array( $this->dataType, $this->dataTypeFactory->getTypeIds() ) ) {
			$this->showErrorHTML( $this->msg( 'wikibase-listproperties-invalid-datatype', $this->dataType )->escaped() );
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
				$this->msg( 'wikibase-listproperties-datatype' )->text()
			) . ' ' .
			Html::rawElement(
				'select',
				array(
					'name' => 'datatype',
					'id' => 'wb-listproperties-datatype',
					'class' => 'wb-select'
				),
				Html::element(
					'option',
					array(
						'value' => '',
						'selected' => $this->dataType === ''
					),
					$this->msg( 'wikibase-listproperties-all' )->text()
				) .
				$dataTypeSelect->getOptionsHtml( $this->dataType )
			) . ' ' .
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
		$propertyIds = $this->getPropertyIds();

		if ( empty( $propertyIds ) ) {
			$this->getOutput()->addWikiMsg( 'specialpage-empty' );
			return;
		}

		$labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
			$this->getLanguage(),
			$propertyIds
		);

		$formatter = $this->entityIdFormatterFactory->getEntityIdFormater( $labelDescriptionLookup );

		$html = Html::openElement( 'ul' );

		foreach ( $propertyIds as $propertyId ) {
			$html .= Html::rawElement( 'li', array(), $formatter->formatEntityId( $propertyId ) );
		}

		$html .= Html::closeElement( 'ul' );
		$this->getOutput()->addHTML( $html );
	}

	/**
	 * @return PropertyId[]
	 */
	private function getPropertyIds() {
		if ( $this->dataType === '' ) {
			$propertyInfoForDataType = $this->propertyInfoStore->getAllPropertyInfo();
		} else {
			$propertyInfoForDataType = $this->propertyInfoStore->getPropertyInfoForDataType( $this->dataType );
		}

		$propertyIds = array();

		foreach ( $propertyInfoForDataType as $numericId => $info ) {
			$propertyIds[] = PropertyId::newFromNumber( $numericId );
		}

		return $propertyIds;
	}

}

