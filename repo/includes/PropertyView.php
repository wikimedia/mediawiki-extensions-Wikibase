<?php

namespace Wikibase;

use DataTypes\DataType;
use Html, ParserOutput, Title, Language, OutputPage, MediaWikiSite;
use Wikibase\Repo\WikibaseRepo;

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
	 * @param EntityContent $propertyContent
	 * @param \Language|null $lang
	 * @param bool $editable
	 * @return string
	 */
	public function getInnerHtml( EntityContent $propertyContent, Language $lang = null, $editable = true ) {
		wfProfileIn( __METHOD__ );

		$html = parent::getInnerHtml( $propertyContent, $lang, $editable );

		/**
		 * @var PropertyContent $propertyContent
		 */
		$html .= $this->getHtmlForDataType(
			$this->getDataType( $propertyContent ),
			$lang,
			$editable
		);

		$html .= $this->getFooterHtml();

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

	protected function getDataType( PropertyContent $content ) {
		return WikibaseRepo::getDefaultInstance()->getDataTypeFactory()
			->getType( $content->getProperty()->getDataTypeId() );
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @since 0.1
	 *
	 * @param DataType $dataType the data type to render
	 * @param Language|null $lang the language to use for rendering. if not given, the local context will be used.
	 * @param bool $editable whether editing is allowed (enabled edit links)
	 *
	 * @return string
	 */
	protected function getHtmlForDataType( DataType $dataType, Language $lang = null, $editable = true ) {
		if( $lang === null ) {
			$lang = $this->getLanguage();
		}

		return wfTemplate( 'wb-property-datatype',
			wfMessage( 'wikibase-datatype-label' )->text(),
			htmlspecialchars( $dataType->getLabel( $lang->getCode() ) )
		);
	}

}
