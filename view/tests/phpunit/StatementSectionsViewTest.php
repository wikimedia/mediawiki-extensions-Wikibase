<?php

namespace Wikibase\View\Tests;

use InvalidArgumentException;
use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;

/**
 * @covers Wikibase\View\StatementSectionsView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class StatementSectionsViewTest extends PHPUnit_Framework_TestCase {

	private function newInstance( array $statementLists = array() ) {
		$templateFactory = new TemplateFactory( [
			'wb-section-heading' => '<HEADING id="$2" class="$3">$1</HEADING>',
		] );

		$statementGrouper = $this->getMock( StatementGrouper::class );
		$statementGrouper->expects( $this->any() )
			->method( 'groupStatements' )
			->will( $this->returnValue( $statementLists ) );

		$statementListView = $this->getMockBuilder( StatementGroupListView::class )
			->disableOriginalConstructor()
			->getMock();
		$statementListView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( '<LIST>' ) );

		return new StatementSectionsView(
			$templateFactory,
			$statementGrouper,
			$statementListView,
			new DummyLocalizedTextProvider()
		);
	}

	/**
	 * @dataProvider statementListsProvider
	 */
	public function testGetHtml( array $statementLists, $expected ) {
		$view = $this->newInstance( $statementLists );
		$html = $view->getHtml( new StatementList() );
		$this->assertSame( $expected, $html );
	}

	public function statementListsProvider() {
		$empty = new StatementList();
		$statements = new StatementList();
		$statements->addNewStatement( new PropertyNoValueSnak( 1 ) );

		return array(
			array(
				array(),
				''
			),
			array(
				array( 'statements' => $empty ),
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>'
			),
			array(
				array( 'statements' => $empty, 'identifiers' => $empty ),
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>'
			),
			array(
				array( 'statements' => $empty, 'P1' => $statements ),
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>'
				. '<HEADING id="P1" class="wikibase-statements'
				. ' wikibase-statements-P1">'
				. '(wikibase-statementsection-p1)</HEADING><LIST>'
			),
		);
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGivenInvalidArray_getHtmlFails( $array ) {
		$view = $this->newInstance( $array );
		$this->setExpectedException( InvalidArgumentException::class );
		$view->getHtml( new StatementList() );
	}

	public function invalidArrayProvider() {
		return array(
			array( array( 'statements' => array() ) ),
			array( array( array() ) ),
			array( array( new StatementList() ) ),
		);
	}

}
