<?php

namespace Wikibase\Test;

use MediaWikiTestCase;
use SpecialPageFactory;
use Wikibase\Repo\View\RepoSpecialPageLinker;

/**
 * @covers Wikibase\Repo\View\RepoSpecialPageLinker
 *
 * @author Adrian Heine < adrian.heine@wikimedia.de >
 */
class RepoSpecialPageLinkerTest extends MediaWikiTestCase {

	protected function setUp() {
		// Make sure wgSpecialPages has the special pages this class uses
		$this->setMwGlobals(
			'wgSpecialPages',
			array(
				'Foo' => 'Foo'
			)
		);

		SpecialPageFactory::resetList();
		$doubleLanguage = $this->getMock( 'Language', array( 'getSpecialPageAliases' ) );
		$doubleLanguage->mCode = 'en';
		$doubleLanguage->expects( $this->any() )
			->method( 'getSpecialPageAliases' )
			->will( $this->returnValue(
				array(
					'Foo' => array( 'Foo' )
				)
			) );

		$this->setMwGlobals(
			'wgContLang',
			$doubleLanguage
		);
		parent::setUp();
	}

	public function testGetLink() {
		$linker = new RepoSpecialPageLinker();

		$link = $linker->getLink( 'Foo' );

		$this->assertRegExp( '/Special:Foo/', $link );
	}

}
