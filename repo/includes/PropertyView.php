<?php

namespace Wikibase;

use DataTypes\DataType;
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
	 * @see EntityView::getInnerHtml
	 *
	 * @param EntityRevision $propertyRevision
	 * @param bool $editable
	 *
	 * @throws \InvalidArgumentException
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $propertyRevision, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$property = $propertyRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new \InvalidArgumentException( '$propertyRevision must contain a Property' );
		}

		/* @var Property $property */
		$head = $this->getHtmlForLabel( $property, $editable ) .
			$this->getHtmlForDescription( $property, $editable ) .
			$this->getHtmlForDataType( $this->getDataType( $property ) );

		$body = $this->getHtmlForAliases( $property, $editable ) .
			$this->getHtmlForToc() .
			$this->getHtmlForTermBox( $property, $editable ) .
			$this->getHtmlForClaims( $property, $editable );

		$foot = '';

		$html = wfTemplate( 'wb-entity-content',
			$head,
			$body,
			$foot
		);

		wfProfileOut( __METHOD__ );
		return $html;
	}

	protected function getFooterHtml() {
		$html = '';

		$footer = $this->msg( 'wikibase-property-footer' );

		if ( !$footer->isBlank() ) {
			$html .= "\n" . $footer->parse();
		}

		return $html;
	}

	protected function getDataType( Property $property ) {
		return WikibaseRepo::getDefaultInstance()->getDataTypeFactory()
			->getType( $property->getDataTypeId() );
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @since 0.1
	 *
	 * @param DataType $dataType the data type to render
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	protected function getHtmlForDataType( DataType $dataType, $editable = true ) {
		$lang = $this->getLanguage();

		return wfTemplate( 'wb-property-datatype',
			wfMessage( 'wikibase-datatype-label' )->text(),
			htmlspecialchars( $dataType->getLabel( $lang->getCode() ) )
		);
	}

}
