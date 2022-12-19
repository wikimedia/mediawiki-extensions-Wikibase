<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWikiIntegrationTestCase;
use Title;
use Wikibase\Client\Hooks\DeletePageNoticeCreator;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\Client\Hooks\DeletePageNoticeCreator
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch < hoo@online.de >
 */
class DeletePageNoticeCreatorTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->setUserLang( 'de' );
	}

	protected function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';

		return new RepoLinker(
			new EntitySourceDefinitions( [], new SubEntityTypesMapper( [] ) ),
			$baseUrl,
			$articlePath,
			$scriptPath
		);
	}

	/**
	 * @dataProvider getPageDeleteNoticeHtmlProvider
	 */
	public function testGetPageDeleteNoticeHtml( $expected, Title $title, $message ) {
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );

		$siteLinkLookup->method( 'getItemIdForLink' )
			->willReturn( new ItemId( 'Q4880' ) );

		$deletePageNotice = new DeletePageNoticeCreator(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);

		$this->assertEquals(
			$expected,
			$deletePageNotice->getPageDeleteNoticeHtml( $title ),
			$message
		);
	}

	public function getPageDeleteNoticeHtmlProvider() {
		$title = Title::makeTitle( NS_MAIN, 'New Amsterdam' );
		$expected = $this->getParsedMessage( 'wikibase-after-page-delete' );

		$title2 = Title::makeTitle( NS_MAIN, 'New York' );
		$title2->wikibasePushedDeleteToRepo = true;
		$expected2 = $this->getParsedMessage( 'wikibase-after-page-delete-queued' );

		return [
			[ $expected, $title, 'after page delete' ],
			[ $expected2, $title2, 'page delete queued' ],
		];
	}

	protected function getParsedMessage( $messageKey ) {
		return '<div class="plainlinks">'
			. wfMessage( $messageKey, 'http://www.example.com/wiki/Special:EntityPage/Q4880' )
				->inLanguage( 'de' )->parse()
			. '</div>';
	}

}
