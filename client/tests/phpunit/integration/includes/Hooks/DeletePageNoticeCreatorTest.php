<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
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

	public function testGetPageDeleteNoticeHtml() {
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );
		$siteLinkLookup->method( 'getItemIdForLink' )
			->willReturn( new ItemId( 'Q4880' ) );
		$title = Title::makeTitle( NS_MAIN, 'New Amsterdam' );

		$deletePageNotice = new DeletePageNoticeCreator(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);
		$actual = $deletePageNotice->getPageDeleteNoticeHtml( $title );

		$expected = $this->getParsedMessage( 'wikibase-after-page-delete' );
		$this->assertSame( $expected, $actual );
	}

	protected function getParsedMessage( $messageKey ) {
		return '<div class="plainlinks">'
			. wfMessage( $messageKey, 'http://www.example.com/wiki/Special:EntityPage/Q4880' )
				->inLanguage( 'de' )->parse()
			. '</div>';
	}

}
