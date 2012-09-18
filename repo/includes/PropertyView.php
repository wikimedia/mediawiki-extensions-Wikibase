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
 */
class PropertyView extends EntityView {

	const VIEW_TYPE = 'property';

	/**
	 * @see EntityView::getInnerHtml
	 */
	public function getInnerHtml( EntityContent $property, Language $lang = null, $editable = true ) {
		$html = parent::getInnerHtml( $property, $lang, $editable );

		// add data value to default entity stuff
		$html .= $this->getHtmlForDataType( $property, $lang, $editable );

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $property the property to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDataType( EntityContent $property, Language $lang = null, $editable = true ) {
		$info = $this->extractEntityInfo( $property );
		//$datatype = $property->getEntity()->getDatatype( $info['lang']->getCode() );

		// todo: use the right stuff to have a string telling which data type this property has
		return Html::element(
			'div',
			array( 'dir' => 'auto' ),
			'' //$datatype
		);
	}
}
