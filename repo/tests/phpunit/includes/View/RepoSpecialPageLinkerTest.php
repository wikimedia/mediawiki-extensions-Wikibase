<?php

namespace Wikibase\Test;

use MediaWikiLangTestCase;
use MediaWiki\MediaWikiServices;
use Wikibase\Repo\View\RepoSpecialPageLinker;

/**
 * @covers Wikibase\Repo\View\RepoSpecialPageLinker
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
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
		return array(
			array( 'SetLabel', array(), '/Special:SetLabel\/?$/' ),
			array( 'SetLabel', array( 'en' ), '/Special:SetLabel\/en\/?$/' ),
			array( 'SetLabel', array( 'en', 'Q5' ), '/Special:SetLabel\/en\/Q5\/?$/' )
		);
	}

}
