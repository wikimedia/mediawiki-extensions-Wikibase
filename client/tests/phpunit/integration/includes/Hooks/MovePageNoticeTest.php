<?php

namespace Wikibase\Client\Tests\Integration\Hooks;

use MediaWikiIntegrationTestCase;
use MovePageForm;
use OutputPage;
use Title;
use Wikibase\Client\Hooks\MovePageNotice;
use Wikibase\Client\RepoLinker;
use Wikibase\DataAccess\EntitySourceDefinitions;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;
use Wikibase\Lib\SubEntityTypesMapper;

/**
 * @covers \Wikibase\Client\Hooks\MovePageNotice
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class MovePageNoticeTest extends MediaWikiIntegrationTestCase {

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

	public function testDoSpecialMovepageAfterMove() {
		$oldTitle = Title::makeTitle( NS_MAIN, 'New Amsterdam' );
		$newTitle = Title::makeTitle( NS_MAIN, 'New York City' );
		$expected = $this->getParsedMessage( 'wikibase-after-page-move' );
		$siteLinkLookup = $this->createMock( SiteLinkLookup::class );

		$siteLinkLookup->method( 'getItemIdForLink' )
			->with( 'dewiki', 'New Amsterdam' )
			->willReturn( new ItemId( 'Q4880' ) );

		$movePageNotice = new MovePageNotice(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);

		$outputPage = $this->createMock( OutputPage::class );

		$outputPage->expects( $this->once() )
				->method( 'addHTML' )
				->with( $expected );

		$outputPage->expects( $this->once() )
				->method( 'addModules' )
				->with( 'wikibase.client.miscStyles' );

		$movePageForm = $this->createMock( MovePageForm::class );
		$movePageForm->expects( $this->once() )
				->method( 'getOutput' )
				->willReturn( $outputPage );

		$movePageNotice->onSpecialMovepageAfterMove( $movePageForm, $oldTitle, $newTitle );

		$this->assertTrue( true ); // The mocks do the assertions we need
	}

	protected function getParsedMessage( $messageKey ) {
		return '<div id="wbc-after-page-move" class="plainlinks">'
			. wfMessage( $messageKey, 'http://www.example.com/wiki/Special:EntityPage/Q4880' )
				->inLanguage( 'de' )->parse()
			. '</div>';
	}

}
