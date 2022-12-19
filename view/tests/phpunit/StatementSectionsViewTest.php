<?php

namespace Wikibase\View\Tests;

use InvalidArgumentException;
use Wikibase\DataModel\Services\Statement\Grouper\StatementGrouper;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\View\DummyLocalizedTextProvider;
use Wikibase\View\StatementGroupListView;
use Wikibase\View\StatementSectionsView;
use Wikibase\View\Template\TemplateFactory;
use Wikibase\View\Template\TemplateRegistry;

/**
 * @covers \Wikibase\View\StatementSectionsView
 *
 * @uses Wikibase\View\Template\Template
 * @uses Wikibase\View\Template\TemplateFactory
 * @uses Wikibase\View\Template\TemplateRegistry
 *
 * @group Wikibase
 * @group WikibaseView
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class StatementSectionsViewTest extends \PHPUnit\Framework\TestCase {

	private function newInstance( array $statementLists = [] ) {
		$templateFactory = new TemplateFactory( new TemplateRegistry( [
			'wb-section-heading' => '<HEADING id="$2" class="$3">$1</HEADING>',
		] ) );

		$statementGrouper = $this->createMock( StatementGrouper::class );
		$statementGrouper->method( 'groupStatements' )
			->willReturn( $statementLists );

		$statementListView = $this->createMock( StatementGroupListView::class );
		$statementListView->method( 'getHtml' )
			->willReturn( '<LIST>' );

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

		return [
			[
				[],
				'',
			],
			[
				[ 'statements' => $empty ],
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>',
			],
			[
				[ 'statements' => $empty, 'identifiers' => $empty ],
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>',
			],
			[
				[ 'statements' => $empty, 'P1' => $statements ],
				'<HEADING id="claims" class="wikibase-statements">'
				. '(wikibase-statementsection-statements)</HEADING><LIST>'
				. '<HEADING id="P1" class="wikibase-statements'
				. ' wikibase-statements-P1">'
				. '(wikibase-statementsection-p1)</HEADING><LIST>',
			],
		];
	}

	/**
	 * @dataProvider invalidArrayProvider
	 */
	public function testGivenInvalidArray_getHtmlFails( $array ) {
		$view = $this->newInstance( $array );
		$this->expectException( InvalidArgumentException::class );
		$view->getHtml( new StatementList() );
	}

	public function invalidArrayProvider() {
		return [
			[ [ 'statements' => [] ] ],
			[ [ [] ] ],
			[ [ new StatementList() ] ],
		];
	}

}
