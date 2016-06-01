<?php

namespace Wikibase\View;

use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use OutOfBoundsException;
use Wikibase\DataModel\Entity\Property;
use Wikibase\View\Template\TemplateFactory;

/**
 * Class for creating views for Property instances.
 * For the Property this basically is what the Parser is for WikitextContent.
 *
 * @since 0.1
 *
 * @license GPL-2.0+
 * @author Daniel Werner
 * @author H. Snater < mediawiki@snater.com >
 */
class PropertyView extends EntityView {

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @var LocalizedTextProvider
	 */
	private $textProvider;

	/**
	 * @see EntityView::__construct
	 *
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param LanguageDirectionalityLookup $languageDirectionalityLookup
	 * @param StatementSectionsView $statementSectionsView
	 * @param DataTypeFactory $dataTypeFactory
	 * @param string $languageCode
	 * @param LocalizedTextProvider $textProvider
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		LanguageDirectionalityLookup $languageDirectionalityLookup,
		StatementSectionsView $statementSectionsView,
		DataTypeFactory $dataTypeFactory,
		$languageCode,
		LocalizedTextProvider $textProvider
	) {
		parent::__construct( $templateFactory, $entityTermsView, $languageDirectionalityLookup, $languageCode );

		$this->statementSectionsView = $statementSectionsView;
		$this->dataTypeFactory = $dataTypeFactory;
		$this->textProvider = $textProvider;
	}

	/**
	 * @see EntityView::getMainHtml
	 *
	 * @param EntityDocument $property
	 *
	 * @throws InvalidArgumentException
	 * @return string HTML
	 */
	protected function getMainHtml( EntityDocument $property ) {
		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$property must contain a Property.' );
		}

		$html = $this->getHtmlForTerms( $property )
			. $this->templateFactory->render( 'wikibase-toc' )
			. $this->getHtmlForDataType( $property->getDataTypeId() )
			. $this->statementSectionsView->getHtml( $property->getStatements() );

		$footer = wfMessage( 'wikibase-property-footer' );
		$footer = $footer->exists() ? $footer->parse() : '';

		if ( $footer !== '' ) {
			$html .= "\n" . $footer;
		}

		return $html;
	}

	/**
	 * Builds and returns the HTML representing a property entity's data type information.
	 *
	 * @param string $propertyType
	 *
	 * @return string HTML
	 */
	private function getHtmlForDataType( $propertyType ) {

		$html = $this->templateFactory->render( 'wb-section-heading',
			htmlspecialchars( $this->textProvider->get( 'wikibase-propertypage-datatype' ) ),
			'datatype',
			'wikibase-propertypage-datatype'
		);

		$dataTypeLabelHtml = '';
		try {
			$dataType = $this->dataTypeFactory->getType( $propertyType );
			$dataTypeLabelHtml = htmlspecialchars( $dataType->getLabel( $this->languageCode ) );
		} catch ( OutOfBoundsException $ex ) {
			$dataTypeLabelHtml .= '<span class="error">' .
				htmlspecialchars( $this->textProvider->get( 'wikibase-propertypage-bad-datatype', [ $propertyType ] ) ) .
				'</span>';
		}
		$html .= $this->templateFactory->render( 'wikibase-propertyview-datatype', $dataTypeLabelHtml );

		return $html;
	}

	/**
	 * @see EntityView::getSideHtml
	 *
	 * @param EntityDocument $entity
	 *
	 * @return string HTML
	 */
	protected function getSideHtml( EntityDocument $entity ) {
		return '';
	}

}
