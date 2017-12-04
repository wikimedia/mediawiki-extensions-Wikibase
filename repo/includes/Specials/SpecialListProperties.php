<?php

namespace Wikibase\Repo\Specials;

use Wikibase\Lib\DataTypeFactory;
use HTMLForm;
use Html;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataTypeSelector;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\Lib\Store\PrefetchingTermLookup;
use Wikibase\Lib\Store\PropertyInfoLookup;

/**
 * Special page to list properties by data type
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
 */
class SpecialListProperties extends SpecialWikibaseQueryPage {

	/**
	 * Max server side caching time in seconds.
	 *
	 * @var int
	 */
	const CACHE_TTL_IN_SECONDS = 30;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var PropertyInfoLookup
	 */
	private $propertyInfoLookup;

	/**
	 * @var LanguageFallbackLabelDescriptionLookup
	 */
	private $labelDescriptionLookup;

	/**
	 * @var string
	 */
	private $dataType;

	/**
	 * @var EntityIdFormatter
	 */
	private $entityIdFormatter;

	/**
	 * @var EntityTitleLookup
	 */
	private $titleLookup;

	/**
	 * @var PrefetchingTermLookup
	 */
	private $prefetchingTermLookup;

	public function __construct(
		DataTypeFactory $dataTypeFactory,
		PropertyInfoLookup $propertyInfoLookup,
		LabelDescriptionLookup $labelDescriptionLookup,
		EntityIdFormatter $entityIdFormatter,
		EntityTitleLookup $titleLookup,
		PrefetchingTermLookup $prefetchingTermLookup
	) {
		parent::__construct( 'ListProperties' );

		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoLookup = $propertyInfoLookup;
		$this->labelDescriptionLookup = $labelDescriptionLookup;
		$this->entityIdFormatter = $entityIdFormatter;
		$this->titleLookup = $titleLookup;
		$this->prefetchingTermLookup = $prefetchingTermLookup;
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
			$this->showQuery();
		}
	}

	/**
	 * Prepares the arguments.
	 *
	 * @param string|null $subPage
	 */
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

		$options = [
			$this->msg( 'wikibase-listproperties-all' )->text() => ''
		];
		$options = array_merge( $options, $dataTypeSelect->getOptionsArray() );

		$formDescriptor = [
			'datatype' => [
				'name' => 'datatype',
				'type' => 'select',
				'id' => 'wb-listproperties-datatype',
				'label-message' => 'wikibase-listproperties-datatype',
				'options' => $options,
				'default' => $this->dataType
			],
			'submit' => [
				'name' => '',
				'type' => 'submit',
				'id' => 'wikibase-listproperties-submit',
				'default' => $this->msg( 'wikibase-listproperties-submit' )->text()
			]
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
	 * @param PropertyId $propertyId
	 *
	 * @return string
	 */
	protected function formatRow( EntityId $propertyId ) {
		$title = $this->titleLookup->getTitleForId( $propertyId );
		if ( !$title->exists() ) {
			return $this->entityIdFormatter->formatEntityId( $propertyId );
		}

		$labelTerm = $this->labelDescriptionLookup->getLabel( $propertyId );

		$row = Html::rawElement(
			'a',
			[
				'title' => $title ? $title->getPrefixedText() : $propertyId->getSerialization(),
				'href' => $title ? $title->getLocalURL() : ''
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
	 * @return PropertyId[]
	 */
	protected function getResult( $offset = 0, $limit = 0 ) {
		$orderedPropertyInfo = $this->getOrderedProperties( $this->getPropertyInfo() );
		$orderedPropertyInfo = array_slice( $orderedPropertyInfo, $offset, $limit, true );

		$propertyIds = array_values( $orderedPropertyInfo );

		$this->prefetchingTermLookup->prefetchTerms( $propertyIds );

		return $propertyIds;
	}

	/**
	 * @param array[] $propertyInfo
	 * @return PropertyId[] A sorted array mapping numeric id to its PropertyId
	 */
	private function getOrderedProperties( array $propertyInfo ) {
		$propertiesById = [];
		foreach ( $propertyInfo as $serialization => $info ) {
			$propertyId = new PropertyId( $serialization );
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
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 */
	protected function getTitleForNavigation() {
		return $this->getPageTitle( $this->dataType );
	}

	/**
	 * @see SpecialPage::getSubpagesForPrefixSearch
	 */
	protected function getSubpagesForPrefixSearch() {
		return $this->dataTypeFactory->getTypeIds();
	}

}
