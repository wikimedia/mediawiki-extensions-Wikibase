<?php

namespace Wikibase;
use Html, ParserOptions, ParserOutput, Title, Language, IContextSource, OutputPage, Sites, Site, MediaWikiSite;

/**
 * Class for creating views for Wikibase\Property instances.
 * For the Wikibase\Property this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater
 */
class PropertyView extends EntityView {

	const VIEW_TYPE = 'property';

	/**
	 * @see EntityView::getInnerHtml
	 *
	 * @param PropertyContent $property
	 */
	public function getInnerHtml( EntityContent $property, Language $lang = null, $editable = true ) {
		$html = parent::getInnerHtml( $property, $lang, $editable );

		// add data value to default entity stuff
		/** @var PropertyContent $property */
		$html .= $this->getHtmlForDataType( $property->getProperty()->getDataType(), $lang, $editable );
		// TODO: figure out where to display type information more nicely

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @since 0.1
	 *
	 * @param \DataTypes\DataType $dataType the data type to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDataType( \DataTypes\DataType $dataType, Language $lang = null, $editable = true ) {
		$langCode = null;
		if ( $lang !== null ) {
			$langCode = $lang->getCode();
		}
		return Html::element(
			'div',
			array( 'class' => 'wb-datatype wb-value-row' ),
			$dataType->getLabel( $langCode ) . ' (' . $dataType->getId() . ')'
		);
	}
}
