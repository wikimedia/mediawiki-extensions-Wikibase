<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use Html;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataTypeSelector;
use Wikibase\Lib\Store\EntityTitleLookup;
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
 * @author Adam Shorland
 */
class SpecialListProperties extends SpecialWikibaseQueryPage {

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
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private labelDescriptionLookup;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @var PropertyId[]
	 */
	private $propertyIds = array();

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	public function __construct() {
		parent::__construct( 'ListProperties' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoStore(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$wikibaseRepo->getLanguageFallbackLabelDescriptionLookupFactory(),
			$wikibaseRepo->getEntityTitleLookup()
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
		LanguageFallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		EntityTitleLookup $titleLookup
	) {
		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->titleLookup = $titleLookup;
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

	/**
	 * Formats a row for display.
	 *
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	protected function formatRow( $propertyId ) {
		$labelDescriptionLookup = $this->getLabelDescriptionLookup();

		$row = $this->getIdHtml( $propertyId );

		try {
			$label = $labelDescriptionLookup->getLabel( $propertyId )->getText();
			$row .= wfMessage( 'colon-separator' )->escaped() . $label;
		} catch ( OutOfBoundsException $e ) {
			$title = $this->titleLookup->getTitleForId( $propertyId );
			if ( !$title->exists() ) {
				$row = $this->getEntityIdFormater()->formatEntityId( $propertyId );
			}
		}

		return $row;
	}

	/**
	 * Returns HTML representing the label in the display language (or an appropriate fallback).
	 *
	 * @param EntityId|null $entityId
	 *
	 * @return string HTML
	 */
	private function getIdHtml( EntityId $entityId = null ) {
		$title = $this->titleLookup->getTitleForId( $entityId );

		$idElement =  Html::element(
			'a',
			array(
				'title' => $title ? $title->getPrefixedText() : '',
				'href' => $title ? $title->getLocalURL() : '',
				'class' => 'wb-itemlink-id'
			),
			$entityId->getSerialization()
		);

		return $idElement;
	}

	private function getLabelDescriptionLookup() {
		if ( !isset( $this->labelDescriptionLookup ) ) {
			$this->labelDescriptionLookup = $this->labelDescriptionLookupFactory->newLabelDescriptionLookup(
				$this->getLanguage(),
				$this->propertyIds
			);
		}
		return $this->labelDescriptionLookup;
	}

	private function getEntityIdFormater() {
		if ( !isset( $this->entityIdFormatter ) ) {
			$this->entityIdFormatter = $this->entityIdFormatterFactory->getEntityIdFormater(
				$this->getLabelDescriptionLookup()
			);
		}
		return $this->entityIdFormatter;
	}

	/**
	 * @param integer $offset Start to include at number of entries from the start title
	 * @param integer $limit Stop at number of entries after start of inclusion
	 *
	 * @return PropertyId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		if ( $this->dataType === '' ) {
			$propertyInfo = $this->propertyInfoStore->getAllPropertyInfo();
		} else {
			$propertyInfo = $this->propertyInfoStore->getPropertyInfoForDataType(
				$this->dataType
			);
		}

		$propertyInfo = array_slice( $propertyInfo, $offset, null, true );

		$propertyIds = array();

		foreach ( $propertyInfo as $numericId => $info ) {
			$propertyIds[] = PropertyId::newFromNumber( $numericId );
		}

		$this->propertyIds = $propertyIds;

		return $propertyIds;
	}

}
