<?php

namespace Wikibase;
use Html, ParserOutput, Title, Language, OutputPage, Sites, MediaWikiSite;

/**
 * Class for creating views for Wikibase\Property instances.
 * For the Wikibase\Property this basically is what the Parser is for WikitextContent.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
class PropertyView extends EntityView {

	/**
	 * @see EntityView::getInnerHtml
	 *
	 * @param EntityContent $property (really ProprtyContent but is abstract)
	 * @param \Language|null $lang
	 * @param bool $editable
	 * @return string
	 */
	public function getInnerHtml( EntityContent $property, Language $lang = null, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$html = parent::getInnerHtml( $property, $lang, $editable );

		//$html .= $this->getHtmlForClaims( $entity, $lang, $editable );

		// add data value to default entity stuff
		/** @var PropertyContent $property */
		$html .= $this->getHtmlForDataType( $property, $lang, $editable );

		wfProfileOut( __METHOD__ );
		return $html;
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @since 0.1
	 *
	 * @param EntityContent $property
	 * @param \Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 * @return string
	 */
	public function getHtmlForDataType( PropertyContent $property, Language $lang = null, $editable = true ) {
		if( $lang === null ) {
			$lang = $this->getLanguage();
		}

		$html = wfTemplate(
			'wb-section-heading',
			wfMessage( 'wikibase-datatype' ),
			'datatype'
		);

		$dataType = $property->getProperty()->getDataType();

		$html .= wfTemplate( 'wb-property-datatype',
			wfMessage( 'wikibase-datatype-label' )->text(),
			htmlspecialchars( $dataType->getLabel( $lang->getCode() ) ),
			'datatype' // added for the toc
		);

		return $html;
	}
}
