<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Title;
use Wikibase\Client\Hooks\UpdateRepoHookHandlers;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;
use Wikibase\Lib\Store\HashSiteLinkStore;
use Wikibase\NamespaceChecker;

/**
 * @covers Wikibase\Client\Hooks\UpdateRepoHookHandlers
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @license GNU GPL v2+
 * @author Marius Hoch < hoo@online.de >
 */
class UpdateRepoHookHandlersTest extends \MediaWikiTestCase {

	public function no_testDoArticleDeleteComplete() {
		$handler = new UpdateRepoHookHandlers(
			new NamespaceChecker( array( ) ),
			$this->getMock( 'JobQueueGroup' ),
			new HashSiteLinkStore(),
			'repowiki',
			'clientwiki',
			true
		);

		$handler->doArticleDeleteComplete($title, User );
	}

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgLang' => Language::factory( 'de' )
		) );
	}

	protected function getRepoLinker() {
		$baseUrl = 'http://www.example.com';
		$articlePath = '/wiki/$1';
		$scriptPath = '';
		$repoNamespaces = array(
			'wikibase-item' => '',
			'wikibase-property' => 'Property:'
		);

		return new RepoLinker( $baseUrl, $articlePath, $scriptPath, $repoNamespaces );
	}

	/**
	 * @dataProvider getMovePageNoticeCaseProvider
	 */
	public function no_testDoSpecialMovepageAfterMove( $expected, Title $oldTitle, Title $newTitle ) {
		$siteLinkLookup = $this->getMock(
			'Wikibase\Lib\Store\SiteLinkTable',
			array( 'getItemIdForSiteLink' ),
			array( 'SiteLinkTable', true )
		);

		$siteLinkLookup->expects( $this->any() )
			->method( 'getItemIdForSiteLink' )
			->with( new SiteLink( 'dewiki', 'New Amsterdam' ) )
			->will( $this->returnValue( new ItemId( 'Q4880' ) ) );

		$movePageNotice = new MovePageNotice(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);

		$outputPage = $this->getMockBuilder( 'OutputPage' )
				->disableOriginalConstructor()
				->getMock();

		$outputPage->expects( $this->once() )
				->method( 'addHtml' )
				->with( $expected );

		$outputPage->expects( $this->once() )
				->method( 'addModules' )
				->with( 'wikibase.client.page-move' );

		$movePageForm = $this->getMock( 'MovePageForm' );
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
