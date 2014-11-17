<?php

namespace Wikibase\Client\Tests\Hooks;

use Language;
use Title;
use Wikibase\Client\MovePageNotice;
use Wikibase\Client\RepoLinker;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @covers Wikibase\Client\MovePageNotice
 *
 * @group WikibaseClient
 * @group Wikibase
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class MovePageNoticeTest extends \MediaWikiTestCase {

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
	 * @dataProvider getMovePageNoticeHtmlProvider
	 */
	public function testGetMovePageNoticeHtml( $expected, Title $oldTitle, Title $newTitle, $message ) {
		$siteLinkLookup = $this->getMock(
			'Wikibase\Lib\Store\SiteLinkTable',
			array( 'getEntityIdForSiteLink' ),
			array( 'SiteLinkTable', true )
		);

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->with( new SiteLink( 'dewiki', 'New Amsterdam' ) )
			->will( $this->returnValue( new ItemId( 'Q4880' ) ) );

		$movePageNotice = new MovePageNotice(
			$siteLinkLookup,
			'dewiki',
			$this->getRepoLinker()
		);

		$this->assertEquals(
			$expected,
			$movePageNotice->getPageMoveNoticeHtml( $oldTitle, $newTitle ),
			$message
		);
	}

	public function getMovePageNoticeHtmlProvider() {
		$oldTitle = Title::newFromText( 'New Amsterdam' );
		$newTitle = Title::newFromText( 'New York City' );
		$expected = $this->getParsedMessage( 'wikibase-after-page-move' );

		$newTitle2 = Title::newFromText( 'New York' );
		$newTitle2->wikibasePushedMoveToRepo = true;
		$expected2 = $this->getParsedMessage( 'wikibase-after-page-move-queued' );

		return array(
			array( $expected, $oldTitle, $newTitle, 'after page move' ),
			array( $expected2, $oldTitle, $newTitle2, 'page move queued' )
		);
	}

	protected function getParsedMessage( $messageKey ) {
		return '<div id="wbc-after-page-move" class="plainlinks">'
			. wfMessage( $messageKey, 'http://www.example.com/wiki/Q4880' )
				->inLanguage( 'de' )->parse()
			. '</div>';
	}

}
