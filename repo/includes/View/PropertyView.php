<?php

namespace Wikibase\Repo\View;

use DataTypes\DataTypeFactory;
use DataTypes\DataType;
use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for creating views for Property instances.
 * For the Property this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
class PropertyView extends EntityView {

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param FingerprintView $fingerprintView
	 * @param ClaimsView $claimsView
	 * @param Language $language
	 * @param bool $editable
	 */
	public function __construct(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		DataTypeFactory $dataTypeFactory,
		Language $language,
		$editable = true
	) {
		parent::__construct( $fingerprintView, $claimsView, $language, $editable );

		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @see EntityView::getMainHtml
	 */
	public function getMainHtml( EntityRevision $entityRevision,
		$editable = true
	) {
		wfProfileIn( __METHOD__ );

		$property = $entityRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain a Property.' );
		}

		$html = parent::getMainHtml( $entityRevision );
		$html .= $this->getHtmlForDataType( $this->getDataType( $property ) );

		$html .= $this->claimsView->getHtml(
			$property->getStatements()->toArray()
		);

		$footer = wfMessage( 'wikibase-property-footer' );

		if ( !$footer->isBlank() ) {
			$html .= "\n" . $footer->parse();
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

	private function getDataType( Property $property ) {
		return $this->dataTypeFactory->getType( $property->getDataTypeId() );
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @param DataType $dataType the data type to render
	 *
	 * @return string
	 */
	private function getHtmlForDataType( DataType $dataType ) {
		return wfTemplate( 'wb-section-heading',
			wfMessage( 'wikibase-propertypage-datatype' )->escaped(),
			'datatype'
		)
		. wfTemplate( 'wikibase-propertyview-datatype',
			htmlspecialchars( $dataType->getLabel( $this->language->getCode() ) )
		);
	}

}
