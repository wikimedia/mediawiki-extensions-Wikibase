<?php

namespace Wikibase\Test;

use Revision;
use Title;
use Wikibase\ContentRetriever;
use Wikibase\Item;
use Wikibase\ItemContent;
use Wikibase\Repo\WikibaseRepo;
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

	/**
	 * @param int $oldId
	 * @param Revision $revision
	 * @param Title $title
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getArticleMock( $oldId, Revision $revision, Title $title )
	{
		$context = $this->getContextMock( $title );
		$page = $this->getPageMock( $revision );

		$article = $this->getMockBuilder( 'Article' )
			->disableOriginalConstructor()
			->getMock();

		$article->expects( $this->any() )
			->method( 'getContext' )
			->will( $this->returnValue( $context ) );

		$article->expects( $this->any() )
			->method( 'getOldID' )
			->will( $this->returnValue( $oldId ) );

		$article->expects( $this->any() )
			->method( 'getPage' )
			->will( $this->returnValue( $page ) );

		$article->expects( $this->any() )
			->method( 'getRevisionFetched' )
			->will( $this->returnValue( $revision ) );

		$article->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		return $article;
	}

	/**
	 * @param Title $title
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getContextMock( Title $title )
	{
		$context = $this->getMockBuilder( 'IContextSource' )
			->disableOriginalConstructor()
			->getMock();

		$context->expects( $this->any() )
			->method( 'getLanguage' )
			->will( $this->returnValue( $title->getPageLanguage() ) );

		$context->expects( $this->any() )
			->method( 'getTitle' )
			->will( $this->returnValue( $title ) );

		return $context;
	}

	/**
	 * @param Revision $revision
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getPageMock( Revision $revision )
	{
		$page = $this->getMockBuilder( 'WikiPage' )
			->disableOriginalConstructor()
			->getMock();

		$page->expects( $this->any() )
			->method( 'getContentHandler' )
			->will( $this->returnValue( $revision->getContentHandler() ) );

		return $page;
	}

	/**
	 * @param array $queryParams
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject
	 */
	private function getRequestMock( array $queryParams )
	{
		$request = $this->getMockBuilder( 'WebRequest' )
			->disableOriginalConstructor()
			->getMock();

		$request->expects( $this->any() )
			->method( 'getCheck' )
			->will( $this->returnValue( isset( $queryParams['diff'] ) ) );

		$request->expects( $this->any() )
			->method( 'getQueryValues' )
			->will( $this->returnValue( $queryParams ) );

		$request->expects( $this->any() )
			->method( 'getVal' )
			->will( $this->returnValue( isset( $queryParams['diff'] ) ? $queryParams['diff'] : null ) );

		return $request;
	}

	public function testGetContentForRequest() {
		$cases = $this->getContentCases();

		foreach( $cases as $case ) {
			/** @var Title $title */
			list( $expected, $oldId, $queryParams, $title, $message ) = $case;

			$revision = Revision::newFromId( $oldId );

			$request = $this->getRequestMock( $queryParams );
			$article = $this->getArticleMock( $oldId, $revision, $title );

			$contentRetriever = new ContentRetriever();
			$content = $contentRetriever->getContentForRequest( $request, $article );

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

		//NOTE: we are assuming here that the item will get stored as a wiki page!
		$store = WikibaseRepo::getDefaultInstance()->getEntityStore();
		$titleLookup = WikibaseRepo::getDefaultInstance()->getEntityTitleLookup();

		$rev = $store->saveEntity( $item, "test", $GLOBALS['wgUser'], EDIT_NEW );
		$title = $titleLookup->getTitleForId( $rev->getEntity()->getId() );

		$revIds = array();

		foreach( $descriptions as $description ) {
			$item->setDescription( 'en', $description );

			$rev = $store->saveEntity( $item, "edit description", $GLOBALS['wgUser'], EDIT_UPDATE );
			$revIds[] = $rev->getRevision();
		}

		/**
		 * @var ItemContent $content2
		 * @var ItemContent $content3
		 */
		$content2 = Revision::newFromId( $revIds[1] )->getContent();
		$content3 = Revision::newFromId( $revIds[2] )->getContent();
		$this->assertNotEquals( $content2, $content3, 'Setup failed' );

		return array(
			array( $content3, $revIds[0], array( 'diff' => '0', 'oldid' => $revIds[0] ), $title, 'diff=0' ),
			array( $content2, $revIds[0], array( 'diff' => $revIds[1], 'oldid' => $revIds[0] ), $title, 'rev id' ),
			array( $content2, $revIds[0], array( 'diff' => 'next', 'oldid' => $revIds[0] ), $title, 'diff=next' ),
			array( $content2, $revIds[1], array( 'diff' => 'prev', 'oldid' => $revIds[1] ), $title, 'diff=prev' ),
			array( $content3, $revIds[1], array( 'diff' => 'cur', 'oldid' => $revIds[1] ), $title, 'diff=cur' ),
			array( $content3, $revIds[1], array( 'diff' => 'c', 'oldid' => $revIds[1] ), $title, 'diff=c' ),
			array( $content3, $revIds[1], array( 'diff' => '0', 'oldid' => $revIds[1] ), $title, 'diff=0' ),
			array( $content3, $revIds[1], array( 'diff' => '', 'oldid' => $revIds[1] ), $title, 'diff=' ),
			array( $content3, $revIds[2], array(), $title, 'no query params' ),
			array( null, $revIds[1], array( 'diff' => '-1', 'oldid' => $revIds[0] ), $title, 'diff=-1' )
		);
	}

}
