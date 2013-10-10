<?php

namespace Wikibase;

use DataTypes\DataType;
use Html;
use InvalidArgumentException;
use Language;
use MediaWikiSite;
use OutputPage;
use ParserOutput;
use Title;
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
	 * @param EntityRevision $entityRevision
	 * @param Language $lang
	 *
	 * @throws \InvalidArgumentException
	 * @internal param bool $editable
	 *
	 * @return string
	 */
	public function getInnerHtml( EntityRevision $entityRevision, Language $lang ) {
		wfProfileIn( __METHOD__ );

		$property = $entityRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$propertyRevision must contain a Property' );
		}

		$html = parent::getInnerHtml( $entityRevision, $lang );

		$html .= $this->getHtmlForDataType( $this->getDataType( $property ), $lang
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
	 * @param Language $lang the language to use for rendering.
	 *
	 * @return string
	 */
	protected function getHtmlForDataType( DataType $dataType, Language $lang ) {
		if( $lang === null ) {
			$lang = $this->getLanguage();
		}

		return wfTemplate( 'wb-property-datatype',
			wfMessage( 'wikibase-datatype-label' )->text(),
			htmlspecialchars( $dataType->getLabel( $lang->getCode() ) )
		);
	}

}
