<?php

namespace Wikibase\Test;

use Wikibase\EntityPerPageBuilderPagesFinder;

/**
 * @covers Wikibase\EntityPerPageBuilderPagesFinder
 *
 * @todo more coverage of all code paths
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseStore
 * @group WikibaseEntityPerPage
 * @group EntityPerPageBuilder
 * @group Database
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class EntityPerPageBuilderPagesFinderTest extends \MediaWikiTestCase {

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

	protected function setUp() {
		parent::setUp();

		$this->db->delete( 'page', '*' );

		$rows = $this->getPageRows();
		$this->db->insert( 'page', $rows );
	}

	public function testGetPages() {
		$pagesFinder = new EntityPerPageBuilderPagesFinder(
			$this->db,
			$this->getEntityNamespaces(),
			true
		);

		$pages = $pagesFinder->getPages( 0, 10 );
		$this->assertEquals( 5, $pages->numRows() );

		$titles = array();

		foreach( $pages as $page ) {
			$titles[] = $page->page_title;
		}

		$expected = array( 'Q150', 'Q151', 'P97', 'P98', 'Q152' );
		$this->assertEquals( $expected, $titles );
	}

	private function getEntityNamespaces() {
		return array(
			'wikibase-item' => 0,
			'wikibase-property' => 102
		);
	}

	private function getPageRows() {
		return array(
			array(
				'page_title' => 'Q150',
				'page_namespace' => 0,
				'page_content_model' => null
			),
			array(
				'page_title' => 'Q151',
				'page_namespace' => 0,
				'page_content_model' => null
			),
			array(
				'page_title' => 'P97',
				'page_namespace' => 102,
				'page_content_model' => null
			),
			array(
				'page_title' => 'P98',
				'page_namespace' => 102,
				'page_content_model' => null
			),
			array(
				'page_title' => 'Q152',
				'page_namespace' => 0,
				'page_content_model' => null
			)
		);
	}
}
