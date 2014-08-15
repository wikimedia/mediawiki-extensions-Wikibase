<?php

namespace Wikibase;

use DataTypes\DataType;
use Wikibase\Repo\View\ClaimsView;
use Wikibase\Repo\WikibaseRepo;

/**
 * Class for creating views for Wikibase\Property instances.
 * For the Wikibase\Property this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
class PropertyView extends EntityView {

	/**
	 * Builds and returns the inner HTML for representing a whole WikibaseEntity. The difference to getHtml() is that
	 * this does not group all the HTMl within one parent node as one entity.
	 *
	 * @param EntityRevision $entityRevision
	 * @param bool $editable
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $entityRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		/* @var Property $property */
		$property = $entityRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new \InvalidArgumentException( '$propertyRevision must contain a Property' );
		}

		$html = '';

		$html .= $this->getHtmlForLabel( $property, $editable );
		$html .= $this->getHtmlForDescription( $property, $editable );

		$html .= wfTemplate( 'wb-entity-header-separator' );

		$html .= $this->getHtmlForAliases( $property, $editable );
		$html .= $this->getHtmlForToc();
		$html .= $this->getHtmlForTermBox( $entityRevision, $editable );

		$html .= $this->getHtmlForDataType( $this->getDataType( $property ) );

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$html .= $this->getHtmlForClaims( $property );
		}

		$footer = $this->msg( 'wikibase-property-footer' );

		if ( !$footer->isBlank() ) {
			$html .= "\n" . $footer->parse();
		}

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Returns the HTML for the claims of this property.
	 *
	 * @param Property $property
	 *
	 * @return string
	 */
	private function getHtmlForClaims( Property $property ) {
		$claimsView = new ClaimsView(
			$this->entityTitleLookup,
			$this->claimHtmlGenerator,
			$this->entityInfoBuilderFactory,
			$this->sectionEditLinkGenerator,
			$this->getLanguage()->getCode()
		);

		return $claimsView->getHtml( $property->getClaims(), 'wikibase-attributes' );
	}

	private function getDataType( Property $property ) {
		return WikibaseRepo::getDefaultInstance()->getDataTypeFactory()
			->getType( $property->getDataTypeId() );
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @param DataType $dataType the data type to render
	 *
	 * @return string
	 */
	private function getHtmlForDataType( DataType $dataType ) {
		$lang = $this->getLanguage();

		return wfTemplate( 'wb-section-heading',
			wfMessage( 'wikibase-propertypage-datatype' )->escaped(),
			'datatype'
		)
		. wfTemplate( 'wb-property-datatype',
			htmlspecialchars( $dataType->getLabel( $lang->getCode() ) )
		);
	}

}
