<?php

namespace Wikibase\Test;

use Revision;
use Wikibase\ContentRetriever;
use Wikibase\Item;
use Wikibase\ItemContent;
use WikiPage;

/**
 * @covers Wikibase\ContentRetriever
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 *
 * @group Wikibase
 * @group WikibaseRepo
 *
 * The database group has as a side effect that temporal database tables are created. This makes
 * it possible to test without poisoning a production database.
 * @group Database
 *
 * Some of the tests takes more time, and needs therefor longer time before they can be aborted
 * as non-functional. The reason why tests are aborted is assumed to be set up of temporal databases
 * that hold the first tests in a pending state awaiting access to the database.
 * @group medium
 */
class ContentRetrieverTest extends \MediaWikiTestCase {

	public function testGetContentForRequest() {
		$cases = $this->getContentCases();

		foreach( $cases as $case ) {
			list( $expected, $oldId, $queryParams, $title, $message ) = $case;

			$article = $this->getMockBuilder( 'Article' )
				->disableOriginalConstructor()
				->getMock();

			$article->expects( $this->any() )
				->method( 'getOldID' )
				->will( $this->returnValue( $oldId ) );

			$request = $this->getMockBuilder( 'WebRequest' )
				->disableOriginalConstructor()
				->getMock();

			$request->expects( $this->any() )
				->method( 'getQueryValues' )
				->will( $this->returnValue( $queryParams ) );

			$contentRetriever = new ContentRetriever();
			$content = $contentRetriever->getContentForRequest( $article, $title, $request );

			$this->assertEquals( $expected, $content, $message );
		}
	}

	private function getContentCases() {
		$item = Item::newEmpty();

		$descriptions = array(
			'Largest city in Germany',
			'Capital of Germany',
			'Best city in Germany'
		);

		$content = new ItemContent( $item );
		$content->save( 'test', null, EDIT_NEW );
		$title = $content->getTitle();
		$page = WikiPage::factory( $title );

		$revIds = array();

		foreach( $descriptions as $description ) {
			$content->getEntity()->setDescription( 'en', $description );
			$page->doEditContent( $content, 'edit description' );
			$revIds[] = $page->getLatest();
		}

		$content2 = Revision::newFromId( $revIds[1] )->getContent();
		$content3 = Revision::newFromId( $revIds[2] )->getContent();

		return array(
			array( $content3, $revIds[0], array( 'diff' => '0', 'oldid' => $revIds[0] ), $title, 'diff=0' ),
			array( $content2, $revIds[0], array( 'diff' => $revIds[1], 'oldid' => $revIds[0] ), $title, 'rev id' ),
			array( $content2, $revIds[0], array( 'diff' => 'next', 'oldid' => $revIds[0] ), $title, 'diff=next' ),
			array( $content2, $revIds[1], array( 'diff' => 'prev', 'oldid' => $revIds[1] ), $title, 'diff=prev' ),
			array( $content3, $revIds[1], array( 'diff' => 'cur', 'oldid' => $revIds[1] ), $title, 'diff=cur' ),
			array( $content3, $revIds[2], array(), $title, 'no query params' ),
			array( null, $revIds[1], array( 'diff' => 'kitten', 'oldid' => $revIds[0] ), $title, 'diff=kitten' )
		);
	}

}
