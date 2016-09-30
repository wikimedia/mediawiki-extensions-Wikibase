<?php

namespace Wikibase\Client\Tests;

use EditPage;
use RequestContext;
use Title;
use WikiPage;
use Wikibase\ClientHooks;

/**
 * @covers Wikibase\ClientHooks
 *
 * @group WikibaseClient
 * @group Database
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class ClientHooksTest extends \PHPUnit_Framework_TestCase {

	protected function setUp() {
		$this->tablesUsed[] = 'wbc_entity_usage';
		parent::setUp();

		self::insertEntityUsageData();
	}

	public function addDBDataOnce() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'page' => [
				[
					'page_title' => 'Vienna',
					'page_namespace' => 0,
					'page_id' => 11,
				],
				[
					'page_title' => 'Berlin',
					'page_namespace' => 0,
					'page_id' => 22,
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$title = Title::newFromText( $row['page_title'], $row['page_namespace'] );
				$page = WikiPage::factory( $title );
				$page->insertOn( $db, $row['page_id'] );
			}
		}
	}

	public static function insertEntityUsageData() {
		$db = wfGetDB( DB_MASTER );
		$dump = [
			'wbc_entity_usage' => [
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'S'
				],
				[
					'eu_page_id' => 11,
					'eu_entity_id' => 'Q3',
					'eu_aspect' => 'O'
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q4',
					'eu_aspect' => 'S'
				],
				[
					'eu_page_id' => 22,
					'eu_entity_id' => 'Q5',
					'eu_aspect' => 'S'
				],
			],
		];

		foreach ( $dump as $table => $rows ) {
			// Clean everything
			$db->delete( $table, '*' );

			foreach ( $rows as $row ) {
				$db->insert( $table, $row );
			}
		}
	}

	public function testOnEditAction() {
		$editor = $this->getEditPage();
		$checkboxes = [];
		$tabindex = 0;
		$res = ClientHooks::onEditAction( $editor, $checkboxes, $tabindex );

		$this->assertTrue( $res );
		$this->assertSame( [], $checkboxes );
		$this->assertSame( 0, $tabindex );
		$this->assertContains(
			'<div class="wikibase-entity-usage">',
			$editor->editFormTextAfterTools
		);
		$this->assertContains( 'Q5', $editor->editFormTextAfterTools );
		$this->assertContains( 'Q4', $editor->editFormTextAfterTools );
		$this->assertNotContains( 'Q3', $editor->editFormTextAfterTools );
		$this->assertContains( 'Sitelink', $editor->editFormTextAfterTools );
	}

	/**
	 * @return EditPage
	 */
	private function getEditPage() {
		$title = $this->getTitle();

		$editor = $this->getMockBuilder( EditPage::class )
			->disableOriginalConstructor()
			->getMock();

		$editor->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		$editor->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $this->getContext() ) );

		$editor->editFormTextAfterTools = '';

		return $editor;
	}

	/**
	 * @return Title
	 */
	private function getTitle() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Berlin' ) );

		$title->expects( $this->any() )
			->method( 'getArticleID' )
			->will( $this->returnValue( 22 ) );

		return $title;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->getTitle();

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

}
