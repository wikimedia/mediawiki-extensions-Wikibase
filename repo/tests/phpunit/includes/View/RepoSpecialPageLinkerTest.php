<?php

namespace Wikibase\Repo\Tests\View;

use MediaWikiLangTestCase;
use MediaWiki\MediaWikiServices;
use Wikibase\Repo\View\RepoSpecialPageLinker;

/**
 * @covers Wikibase\Repo\View\RepoSpecialPageLinker
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Adrian Heine <adrian.heine@wikimedia.de>
 */
class RepoSpecialPageLinkerTest extends MediaWikiLangTestCase {

	protected function setUp() {
		parent::setUp();

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
	}

	protected function tearDown() {
		parent::tearDown();

		$services = MediaWikiServices::getInstance();
		$services->resetServiceForTesting( 'TitleFormatter' );
		$services->resetServiceForTesting( 'TitleParser' );
		$services->resetServiceForTesting( '_MediaWikiTitleCodec' );
	}

	/**
	 * @dataProvider getLinkProvider
	 *
	 * @param string $specialPageName
	 * @param string[] $subPageParams
	 * @param string $expectedMatch
	 */
	public function testGetLink( $specialPageName, array $subPageParams, $expectedMatch ) {
		$linker = new RepoSpecialPageLinker();

		$link = $linker->getLink( $specialPageName, $subPageParams );

		$this->assertRegExp( $expectedMatch, $link );
	}

	public function getLinkProvider() {
		return [
			[ 'SetLabel', [], '/Special:SetLabel\/?$/' ],
			[ 'SetLabel', [ 'en' ], '/Special:SetLabel\/en\/?$/' ],
			[ 'SetLabel', [ 'en', 'Q5' ], '/Special:SetLabel\/en\/Q5\/?$/' ]
		];
	}

}
