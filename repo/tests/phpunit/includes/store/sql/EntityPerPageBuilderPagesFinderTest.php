<?php

namespace Wikibase\Test;

use Wikibase\EntityPerPageBuilderPagesFinder;

/**
 * @covers Wikibase\EntityPerPageBuilderPagesFinder
 *
 * @todo cover all code paths
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

	protected function tearDown() {
		parent::tearDown();

		$this->db->delete( 'page', '*' );
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
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.596620774293,
				'page_len' => 193,
				'page_latest' => 4
			),
			array(
				'page_title' => 'Q151',
				'page_namespace' => 0,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.621620829746,
				'page_len' => 837,
				'page_latest' => 18
			),
			array(
				'page_title' => 'P97',
				'page_namespace' => 102,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.611068689513,
				'page_len' => 639,
				'page_latest' => 10
			),
			array(
				'page_title' => 'P98',
				'page_namespace' => 102,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.755673604599,
				'page_len' => 188,
				'page_latest' => 11
			),
			array(
				'page_title' => 'Q152',
				'page_namespace' => 0,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.214882743565,
				'page_len' => 277,
				'page_latest' => 7
			)
		);
	}
}
