<?php
namespace Wikibase\Client\Tests\Usage;

use MediaWikiTestCase;
use PHPUnit_Framework_TestCase;
use Title;
use Wikibase\Client\Store\TitleFactory;
use Wikibase\Lib\Store\StorageException;
use WikiPage;
use WikitextContent;

/**
 * @covers Wikibase\Client\Store\TitleFactory
 *
 * @group Database
 * @group Wikibase
 * @group WikibaseClient
 * @group WikibaseClientStore
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TitleFactoryTest extends MediaWikiTestCase {

	private function makePage( $name ) {
		$title = Title::makeTitle( NS_HELP, $name );
		$page = new WikiPage( $title );

		if ( !$page->exists() ) {
			$content = new WikitextContent( $name );
			$status = $page->doEditContent( $content, $name, EDIT_NEW );

			if ( !$status->isOK() ) {
				throw new StorageException( 'Failed to create page ' . $name );
			}
		}

		return $title;
	}

	public function testNewFromID() {
		$orignalTitle = $this->makePage( 'TitleFactoryTest-testNewFromID' );
		$pageId = $orignalTitle->getArticleID();

		$factory = new TitleFactory();
		$title = $factory->newFromID( $pageId );

		$this->assertEquals( $orignalTitle->getFullText(), $title->getFullText() );
	}

	public function testNewFromText() {
		$name = 'TitleFactoryTest-testNewFromText';

		$factory = new TitleFactory();
		$title = $factory->newFromText( $name, NS_HELP );

		$this->assertEquals( NS_HELP, $title->getNamespace() );
		$this->assertEquals( $name, $title->getText() );
	}

	public function testMakeTitle() {
		$ns = NS_HELP;
		$name = 'TitleFactoryTest-testMakeTitle';
		$fragment = 'Synopsis';

		$factory = new TitleFactory();
		$title = $factory->makeTitle( $ns, $name, $fragment );

		$this->assertEquals( $ns, $title->getNamespace() );
		$this->assertEquals( $name, $title->getText() );
		$this->assertEquals( $fragment, $title->getFragment() );
	}

	public function testMakeTitleSafe() {
		$ns = NS_HELP;
		$name = 'TitleFactoryTest-testMakeTitleSafe';
		$fragment = 'Synopsis';

		$factory = new TitleFactory();
		$title = $factory->makeTitleSafe( $ns, $name, $fragment );

		$this->assertEquals( $ns, $title->getNamespace() );
		$this->assertEquals( $name, $title->getText() );
		$this->assertEquals( $fragment, $title->getFragment() );
	}

}
