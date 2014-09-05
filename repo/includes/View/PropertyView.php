<?php

namespace Wikibase\Repo\View;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;

/**
 * Class for creating views for Property instances.
 * For the Property this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class PropertyView extends EntityView {

	/**
	 * @var FingerprintView
	 */
	private $fingerprintView;

	/**
	 * @var ClaimsView
	 */
	private $claimsView;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	public function __construct(
		FingerprintView $fingerprintView,
		ClaimsView $claimsView,
		DataTypeFactory $dataTypeFactory,
		Language $language
	) {
		parent::__construct( $language );

		$this->fingerprintView = $fingerprintView;
		$this->claimsView = $claimsView;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @see EntityView::getInnerHtml
	 */
	public function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		$property = $entityRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain a Property.' );
		}

		$html = '';
		$html .= $this->fingerprintView->getHtml( $property->getFingerprint(), $property->getId(), $editable );
		$html .= $this->getHtmlForTermBox( $entityRevision );

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$html .= $this->claimsView->getHtml( $property->getClaims(), 'wikibase-attributes' );
		}

		$html .= $this->getHtmlForDataType( $this->getDataType( $property ) );

		$footer = wfMessage( 'wikibase-property-footer' );

		if ( !$footer->isBlank() ) {
			$html .= $footer->parse();
		}

		return $html;
	}

	private function getHtmlForTermBox( EntityRevision $entityRevision ) {
		if ( $entityRevision->getEntity()->getId() ) {
			// Placeholder for a termbox for the present item.
			// EntityViewPlaceholderExpander must know about the parameters used here.
			return $this->textInjector->newMarker(
				'termbox',
				$entityRevision->getEntity()->getId()->getSerialization(),
				$entityRevision->getRevision()
			);
		}

		return '';
	}

	private function getHtmlForDataType( DataType $dataType ) {
		return wfTemplate( 'wb-section-heading',
			wfMessage( 'wikibase-propertypage-datatype' )->escaped(),
			'datatype'
		)
		. wfTemplate( 'wb-property-datatype',
			htmlspecialchars( $dataType->getLabel( $this->language->getCode() ) )
		);
	}

	private function getDataType( Property $property ) {
		return $this->dataTypeFactory->getType( $property->getDataTypeId() );
	}

}
