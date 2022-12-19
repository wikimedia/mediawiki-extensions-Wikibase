<?php

namespace Wikibase\Repo\Specials;

use Html;
use HTMLForm;
use LogicException;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Term\TermTypes;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\FallbackLabelDescriptionLookupFactory;
use Wikibase\Lib\Store\PropertyInfoLookup;
use Wikibase\Repo\DataTypeSelector;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * Special page to list properties by data type
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class SpecialListProperties extends SpecialWikibaseQueryPage {

	/**
	 * Max server side caching time in seconds.
	 */
	protected const CACHE_TTL_IN_SECONDS = 30;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoLookup
	 */
	private $propertyInfoLookup;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var FallbackLabelDescriptionLookupFactory
	 */
	private $labelDescriptionLookupFactory;

	/**
	 * @var EntityIdFormatterFactory
	 */
	private $entityIdFormatterFactory;

	/** @var FallbackLabelDescriptionLookup */
	private $labelDescriptionLookup;

	public function __construct(
		DataTypeFactory $dataTypeFactory,
		PropertyInfoLookup $propertyInfoLookup,
		FallbackLabelDescriptionLookupFactory $labelDescriptionLookupFactory,
		EntityIdFormatterFactory $entityIdFormatterFactory,
		EntityTitleLookup $titleLookup
	) {
		parent::__construct( 'ListProperties' );

		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoLookup = $propertyInfoLookup;
		$this->labelDescriptionLookupFactory = $labelDescriptionLookupFactory;
		$this->entityIdFormatterFactory = $entityIdFormatterFactory;
		$this->titleLookup = $titleLookup;
	}

	/**
	 * @see SpecialWikibasePage::execute
	 *
	 * @param string|null $subPage
	 */
	public function execute( $subPage ) {
		parent::execute( $subPage );

		$output = $this->getOutput();
		$output->setCdnMaxage( static::CACHE_TTL_IN_SECONDS );

		$this->prepareArguments( $subPage );
		$this->showForm();

		if ( $this->dataType !== null ) {
			$this->showQuery( [], $this->dataType );
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @param string|null $subPage
	 */
	private function prepareArguments( $subPage ) {
		$request = $this->getRequest();

		$this->dataType = $request->getText( 'datatype', $subPage ?: '' );
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

		$options = [
			$this->msg( 'wikibase-listproperties-all' )->text() => '',
		];
		$options = array_merge( $options, $dataTypeSelect->getOptionsArray() );

		$formDescriptor = [
			'datatype' => [
				'name' => 'datatype',
				'type' => 'select',
				'id' => 'wb-listproperties-datatype',
				'label-message' => 'wikibase-listproperties-datatype',
				'options' => $options,
				'default' => $this->dataType,
			],
			'submit' => [
				'name' => '',
				'type' => 'submit',
				'id' => 'wikibase-listproperties-submit',
				'default' => $this->msg( 'wikibase-listproperties-submit' )->text(),
			],
		];

		HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() )
			->setId( 'wb-listproperties-form' )
			->setMethod( 'get' )
			->setWrapperLegendMsg( 'wikibase-listproperties-legend' )
			->suppressDefaultSubmit()
			->setSubmitCallback( function () {
			} )
			->show();
	}

	/**
	 * Formats a row for display.
	 *
	 * @param NumericPropertyId $propertyId
	 *
	 * @return string
	 * @suppress PhanParamSignatureMismatch Uses intersection types
	 */
	protected function formatRow( EntityId $propertyId ) {
		$title = $this->titleLookup->getTitleForId( $propertyId );
		$language = $this->getLanguage();

		if ( !$title->exists() ) {
			return $this->entityIdFormatterFactory
				->getEntityIdFormatter( $language )
				->formatEntityId( $propertyId );
		}

		$labelTerm = $this->getLabelDescriptionLookup()
			->getLabel( $propertyId );

		$row = Html::rawElement(
			'a',
			[
				'title' => $title ? $title->getPrefixedText() : $propertyId->getSerialization(),
				'href' => $title ? $title->getLocalURL() : '',
			],
			Html::rawElement(
				'span',
				[ 'class' => 'wb-itemlink' ],
				Html::element(
					'span',
					[
						'class' => 'wb-itemlink-label',
						'lang' => $labelTerm ? $labelTerm->getActualLanguageCode() : '',
					],
					$labelTerm ? $labelTerm->getText() : ''
				) .
				( $labelTerm ? ' ' : '' ) .
				Html::element(
					'span',
					[ 'class' => 'wb-itemlink-id' ],
					'(' . $propertyId->getSerialization() . ')'
				)
			)
		);

		return $row;
	}

	/**
	 * @param integer $offset Start to include at number of entries from the start title
	 * @param integer $limit Stop at number of entries after start of inclusion
	 *
	 * @return NumericPropertyId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$orderedPropertyInfo = $this->getOrderedProperties( $this->getPropertyInfo() );
		$orderedPropertyInfo = array_slice( $orderedPropertyInfo, $offset, $limit, true );

		$propertyIds = array_values( $orderedPropertyInfo );
		$this->initLabelDescriptionLookup( $propertyIds );

		return $propertyIds;
	}

	/**
	 * @param array[] $propertyInfo
	 * @return NumericPropertyId[] A sorted array mapping numeric id to its NumericPropertyId
	 */
	private function getOrderedProperties( array $propertyInfo ) {
		$propertiesById = [];
		foreach ( $propertyInfo as $serialization => $info ) {
			$propertyId = new NumericPropertyId( $serialization );
			$propertiesById[$propertyId->getNumericId()] = $propertyId;
		}
		ksort( $propertiesById );

		return $propertiesById;
	}

	/**
	 * @return array[] An associative array mapping property IDs to info arrays.
	 */
	private function getPropertyInfo() {
		if ( $this->dataType === '' ) {
			$propertyInfo = $this->propertyInfoLookup->getAllPropertyInfo();
		} else {
			$propertyInfo = $this->propertyInfoLookup->getPropertyInfoForDataType(
				$this->dataType
			);
		}

		return $propertyInfo;
	}

	/**
	 * @inheritDoc
	 */
	protected function getSubpagesForPrefixSearch() {
		return $this->dataTypeFactory->getTypeIds();
	}

	/** Initialize the label+description lookup, prefetching labels for the given entity IDs. */
	private function initLabelDescriptionLookup( array $entityIds ): void {
		if ( $this->labelDescriptionLookup !== null ) {
			throw new LogicException( 'LabelDescriptionLookup already initialized!' );
		}

		$this->labelDescriptionLookup = $this->labelDescriptionLookupFactory
			->newLabelDescriptionLookup( $this->getLanguage(), $entityIds, [ TermTypes::TYPE_LABEL ] );
	}

	/** Get the label+description lookup, asserting that it has already been initialized. */
	private function getLabelDescriptionLookup(): FallbackLabelDescriptionLookup {
		if ( $this->labelDescriptionLookup === null ) {
			throw new LogicException( 'LabelDescriptionLookup not initialized!' );
		}

		return $this->labelDescriptionLookup;
	}

}
