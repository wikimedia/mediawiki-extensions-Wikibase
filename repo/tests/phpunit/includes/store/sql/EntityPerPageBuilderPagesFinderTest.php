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

	private static $pagesUsed = array();

	public function __construct( $name = null, $data = array(), $dataName = '' ) {
		parent::__construct( $name, $data, $dataName );

		$this->tablesUsed[] = 'wb_entity_per_page';
	}

	protected function setUp() {
		parent::setUp();

		$pages = $this->getPageRows();

		foreach( $pages as $page ) {
			$nextId = $this->db->nextSequenceValue( 'page_page_id_seq' );

			if ( $nextId !== null ) {
				$page['page_id'] = $nextId;
			}

			$this->db->insert( 'page', array( $page ) );
			self::$pagesUsed[] = $page['page_title'];
		}
	}

	protected function tearDown() {
		parent::tearDown();

		$this->db->delete(
			'page',
			array( 'page_title IN (' . $this->db->makeList( self::$pagesUsed ) . ')' )
		);
	}

	public function testGetPages() {
		$pagesFinder = new EntityPerPageBuilderPagesFinder(
			$this->db,
			$this->getEntityNamespaces(),
			true
		);

		$pages = $pagesFinder->getPages( 0, 1000 );

		$titles = array();

		foreach( $pages as $page ) {
			$titles[] = $page->page_title;
		}

		foreach( self::$pagesUsed as $expectedTitle ) {
			$this->assertTrue( in_array( $expectedTitle, $titles ), "expected title $expectedTitle found" );
		}
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
				'page_title' => 'Q15001',
				'page_namespace' => 0,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.596620774293,
				'page_len' => 193,
				'page_latest' => 4
			),
			array(
				'page_title' => 'Q15002',
				'page_namespace' => 0,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.621620829746,
				'page_len' => 837,
				'page_latest' => 18
			),
			array(
				'page_title' => 'P9701',
				'page_namespace' => 102,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.611068689513,
				'page_len' => 639,
				'page_latest' => 10
			),
			array(
				'page_title' => 'P9702',
				'page_namespace' => 102,
				'page_content_model' => null,
				'page_restrictions' => '',
				'page_random' => 0.755673604599,
				'page_len' => 188,
				'page_latest' => 11
			),
			array(
				'page_title' => 'Q15003',
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
