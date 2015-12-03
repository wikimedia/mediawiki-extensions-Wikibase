<?php

namespace Wikibase\View;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use InvalidArgumentException;
use Language;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\EntityRevision;
use Wikibase\View\Template\TemplateFactory;

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
	 * @var StatementGrouper
	 */
	private $statementGrouper;

	/**
	 * @var StatementSectionsView
	 */
	private $statementSectionsView;

	/**
	 * @var DataTypeFactory
	 */
	private $dataTypeFactory;

	/**
	 * @param TemplateFactory $templateFactory
	 * @param EntityTermsView $entityTermsView
	 * @param StatementGrouper $statementGrouper
	 * @param StatementSectionsView $statementSectionsView
	 * @param DataTypeFactory $dataTypeFactory
	 * @param Language $language
	 */
	public function __construct(
		TemplateFactory $templateFactory,
		EntityTermsView $entityTermsView,
		StatementGrouper $statementGrouper,
		StatementSectionsView $statementSectionsView,
		DataTypeFactory $dataTypeFactory,
		Language $language
	) {
		parent::__construct( $templateFactory, $entityTermsView, $language );

		$this->statementGrouper = $statementGrouper;
		$this->statementSectionsView = $statementSectionsView;
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

		$statementLists = $this->statementGrouper->groupStatements( $property->getStatements() );
		$html .= $this->statementSectionsView->getHtml( $statementLists );

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
			'datatype',
			'wikibase-propertypage-datatype'
		)
		. $this->templateFactory->render( 'wikibase-propertyview-datatype',
			htmlspecialchars( $dataType->getLabel( $this->language->getCode() ) )
		);
	}

}
