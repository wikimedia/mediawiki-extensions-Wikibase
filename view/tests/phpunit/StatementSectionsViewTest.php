<?php

namespace Wikibase\Test;

use Language;
use MediaWikiTestCase;
use Wikibase\DataModel\Services\Statement\Grouper\NullStatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers Wikibase\View\StatementSectionsView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class StatementSectionsViewTest extends MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgLang' => Language::factory( 'qqx' ),
		) );
	}

	private function newInstance( array $statementLists = array() ) {
		$templateFactory = new TemplateFactory( new TemplateRegistry( array(
			'wb-section-heading' => '<HEADING id="$2" class="$3">$1</HEADING>',
		) ) );

		$statementGrouper = $this->getMock(
			'Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper'
		);
		$statementGrouper->expects( $this->any() )
			->method( 'groupStatements' )
			->will( $this->returnValue( $statementLists ) );

		$statementListView = $this->getMockBuilder( 'Wikibase\View\StatementGroupListView' )
			->disableOriginalConstructor()
			->getMock();
		$statementListView->expects( $this->any() )
			->method( 'getHtml' )
			->will( $this->returnValue( '<LIST>' ) );

		return new StatementSectionsView(
			$templateFactory,
			$statementGrouper,
			$statementListView
		);
	}

	/**
	 * @dataProvider statementListProvider
	 */
	public function testGetHtml( array $statementLists, $expected ) {
		$view = $this->newInstance( $statementLists );
		$html = $view->getHtml( new StatementList() );
		$this->assertSame( $expected, $html );
	}

	public function statementListProvider() {
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
		$this->setExpectedException( 'InvalidArgumentException' );
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
