<?php

namespace Wikibase\Repo\View;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Property;
use Wikibase\EntityRevision;
use Wikibase\Template\TemplateFactory;

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
	 * @var StatementGroupListView
	 */
	private $statementGroupListView;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param StatementGroupListView $statementGroupListView
	 * @param DataTypeFactory $dataTypeFactory
	 * @param Language $language
	 * @param bool $editable
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		StatementGroupListView $statementGroupListView,
		DataTypeFactory $dataTypeFactory,
		Language $language,
		$editable = true
	) {
		parent::__construct( $templateFactory, $entityTermsView, $language, $editable );

		$this->statementGroupListView = $statementGroupListView;
		$this->dataTypeFactory = $dataTypeFactory;
	}

	/**
	 * @see EntityView::getMainHtml
	 */
	public function getMainHtml( EntityRevision $entityRevision ) {
		$property = $entityRevision->getEntity();

		if ( !( $property instanceof Property ) ) {
			throw new InvalidArgumentException( '$entityRevision must contain a Property.' );
		}

		$html = parent::getMainHtml( $entityRevision );
		$html .= $this->getHtmlForDataType( $this->getDataType( $property ) );

		$html .= $this->statementGroupListView->getHtml(
			$property->getStatements()->toArray()
		);

		$footer = wfMessage( 'wikibase-property-footer' );

		if ( !$footer->isBlank() ) {
			$html .= "\n" . $footer->parse();
		}

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
		return $this->templateFactory->render( 'wb-section-heading',
			wfMessage( 'wikibase-propertypage-datatype' )->escaped(),
			'datatype'
		)
		. $this->templateFactory->render( 'wikibase-propertyview-datatype',
			htmlspecialchars( $dataType->getLabel( $this->language->getCode() ) )
		);
	}

}
