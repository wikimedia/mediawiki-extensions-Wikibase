<?php

namespace Wikibase\Client\Tests\Hooks;

use MovePageForm;
use OutputPage;
use Title;
use Wikibase\Client\Hooks\MovePageNotice;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\SiteLinkLookup;

/**
 * @covers Wikibase\Client\Hooks\MovePageNotice
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Marius Hoch < hoo@online.de >
 */
class MovePageNoticeTest extends \MediaWikiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setUserLang( 'de' );
	}

	protected function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';
		$repoNamespaces = array(
			'item' => '',
			'property' => 'Property:'
		);

		return new RepoLinker( $baseUrl, $articlePath, $scriptPath, $repoNamespaces );
	}

	/**
	 * @dataProvider getMovePageNoticeCaseProvider
	 */
	public function testDoSpecialMovepageAfterMove( $expected, Title $oldTitle, Title $newTitle ) {
		$siteLinkLookup = $this->getMock( SiteLinkLookup::class );

		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForLink' )
			->with( 'dewiki', 'New Amsterdam' )
			->will( $this->returnValue( new ItemId( 'Q4880' ) ) );

		$movePageNotice = new MovePageNotice(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);

		$outputPage = $this->getMockBuilder( OutputPage::class )
				->disableOriginalConstructor()
				->getMock();

		$outputPage->expects( $this->once() )
				->method( 'addHtml' )
				->with( $expected );

		$outputPage->expects( $this->once() )
				->method( 'addModules' )
				->with( 'wikibase.client.page-move' );

		$movePageForm = $this->getMock( MovePageForm::class );
		$movePageForm->expects( $this->once() )
				->method( 'getOutput' )
				->will( $this->returnValue( $outputPage ) );

		$movePageNotice->doSpecialMovepageAfterMove( $movePageForm, $oldTitle, $newTitle );

		$this->assertTrue( true ); // The mocks do the assertions we need
	}

	public function getMovePageNoticeCaseProvider() {
		$oldTitle = Title::newFromText( 'New Amsterdam' );
		$newTitle = Title::newFromText( 'New York City' );
		$expected = $this->getParsedMessage( 'wikibase-after-page-move' );

		$newTitle2 = Title::newFromText( 'New York' );
		$newTitle2->wikibasePushedMoveToRepo = true;
		$expected2 = $this->getParsedMessage( 'wikibase-after-page-move-queued' );

		return array(
			'after page move' => array( $expected, $oldTitle, $newTitle ),
			'page move queued' => array( $expected2, $oldTitle, $newTitle2 )
		);
	}

	protected function getParsedMessage( $messageKey ) {
		return '<div id="wbc-after-page-move" class="plainlinks">'
			. wfMessage( $messageKey, 'http://www.example.com/wiki/Q4880' )
				->inLanguage( 'de' )->parse()
			. '</div>';
	}

}
