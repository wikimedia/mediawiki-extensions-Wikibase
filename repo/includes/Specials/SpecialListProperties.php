<?php

namespace Wikibase\Repo\Specials;

use DataTypes\DataTypeFactory;
use HTMLForm;
use Html;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataTypeSelector;
use Wikibase\LanguageFallbackChainFactory;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookup;
use Wikibase\PropertyInfoStore;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\BufferingTermLookup;
use Wikibase\View\EntityIdFormatterFactory;

/**
 * Special page to list properties by data type
 *
 * @since 0.5
 *
 * @license GPL-2.0+
 * @author Bene* < benestar.wikimedia@gmail.com >
 * @author Addshore
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
	 * @var BufferingTermLookup
	 */
	private $bufferingTermLookup;

	public function __construct() {
		parent::__construct( 'ListProperties' );

		$wikibaseRepo = WikibaseRepo::getDefaultInstance();

		$this->initServices(
			$wikibaseRepo->getDataTypeFactory(),
			$wikibaseRepo->getStore()->getPropertyInfoStore(),
			$wikibaseRepo->getEntityIdHtmlLinkFormatterFactory(),
			$wikibaseRepo->getLanguageFallbackChainFactory(),
			$wikibaseRepo->getEntityTitleLookup(),
			$wikibaseRepo->getBufferingTermLookup()
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
		LanguageFallbackChainFactory $languageFallbackChainFactory,
		EntityTitleLookup $titleLookup,
		BufferingTermLookup $bufferingTermLookup
	) {
		$fallbackMode = LanguageFallbackChainFactory::FALLBACK_ALL;
		$this->labelDescriptionLookup = new LanguageFallbackLabelDescriptionLookup(
			$bufferingTermLookup,
			$languageFallbackChainFactory->newFromLanguage( $this->getLanguage(), $fallbackMode )
		);

		$this->dataTypeFactory = $dataTypeFactory;
		$this->propertyInfoStore = $propertyInfoStore;
		$this->entityIdFormatter = $entityIdFormatterFactory->getEntityIdFormatter(
			$this->labelDescriptionLookup
		);
		$this->titleLookup = $titleLookup;
		$this->bufferingTermLookup = $bufferingTermLookup;
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
		$output->setCdnMaxage( static::CACHE_TTL_IN_SECONDS );

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

		$options = array(
			$this->msg( 'wikibase-listproperties-all' )->text() => ''
		);
		$options = array_merge( $options, array_flip( $dataTypeSelect->getOptionsArray() ) );

		$formDescriptor = array(
			'datatype' => array(
				'name' => 'datatype',
				'type' => 'select',
				'id' => 'wb-listproperties-datatype',
				'label-message' => 'wikibase-listproperties-datatype',
				'options' => $options,
				'default' => $this->dataType
			),
			'submit' => array(
				'name' => '',
				'type' => 'submit',
				'id' => 'wikibase-listproperties-submit',
				'default' => $this->msg( 'wikibase-listproperties-submit' )->text()
			)
		);

		HTMLForm::factory( 'inline', $formDescriptor, $this->getContext() )
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
	protected function formatRow( $propertyId ) {
		$title = $this->titleLookup->getTitleForId( $propertyId );
		if ( !$title->exists() ) {
			return $this->entityIdFormatter->formatEntityId( $propertyId );
		}

		$labelTerm = $this->labelDescriptionLookup->getLabel( $propertyId );

		$row = Html::rawElement(
			'a',
			array(
				'title' => $title ? $title->getPrefixedText() : $propertyId->getSerialization(),
				'href' => $title ? $title->getLocalURL() : ''
			),
			Html::rawElement(
				'span',
				array( 'class' => 'wb-itemlink' ),
				Html::element(
					'span',
					array(
						'class' => 'wb-itemlink-label',
						'lang' => $labelTerm ? $labelTerm->getLanguageCode() : '',
					),
					$labelTerm ? $labelTerm->getText() : ''
				) .
				( $labelTerm ? ' ' : '' ) .
				Html::element(
					'span',
					array( 'class' => 'wb-itemlink-id' ),
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
		$propertyInfo = array_slice( $this->getPropertyInfo(), $offset, $limit, true );

		$propertyIds = [];

		foreach ( $propertyInfo as $numericId => $info ) {
			$propertyIds[] = PropertyId::newFromNumber( $numericId );
		}

		$this->bufferingTermLookup->prefetchTerms( $propertyIds );

		return $propertyIds;
	}

	/**
	 * @return array[] An associative array mapping property IDs to info arrays.
	 */
	private function getPropertyInfo() {
		if ( $this->dataType === '' ) {
			$propertyInfo = $this->propertyInfoStore->getAllPropertyInfo();
		} else {
			$propertyInfo = $this->propertyInfoStore->getPropertyInfoForDataType(
				$this->dataType
			);
		}

		// NOTE: $propertyInfo uses numerical property IDs as keys!
		ksort( $propertyInfo );
		return $propertyInfo;
	}

	/**
	 * @see SpecialWikibaseQueryPage::getTitleForNavigation
	 *
	 * @since 0.4
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
