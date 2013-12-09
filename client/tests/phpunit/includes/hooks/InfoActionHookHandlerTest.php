<?php

namespace Wikibase\Test;

use Language;
use RequestContext;
use Title;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\NamespaceChecker;
use Wikibase\RepoLinker;
use Wikibase\SiteLinkTable;

/**
 * @covers Wikibase\Client\Hooks\InfoActionHookHandler
 *
 * @since 0.5
 *
 * @group WikibaseClient
 * @group InfoActionHookHandler
 * @group Database
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandlerTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( $expected, $context, $pageInfo, $enabled, $entityId, $message ) {
		$hookHandler = $this->newHookHandler( $enabled, $entityId );
		$pageInfo = $hookHandler->handle( $context, $pageInfo );

		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function handleProvider() {
		$context = $this->getContext();

		$cases = array();

		$cases[] = array(
			array(
				'header-basic' => array(
					array(
						$context->msg( 'wikibase-pageinfo-entity-id' ),
						'https://www.wikidata.org/wiki/Q4'
					)
				)
			),
			$context, array(), true, new ItemId( 'Q4' ),
			'item id link'
		);

		$cases[] = array(
			array(), $context, array(), false, new ItemId( 'Q4' ),
			'namespace does not have wikibase enabled'
		);

		$cases[] = array(
			array(
				'header-basic' => array(
					array(
						$context->msg( 'wikibase-pageinfo-entity-id' ),
						$context->msg( 'wikibase-pageinfo-entity-id-none' )
					)
				)
			),
			$context, array(), true, false,
			'page is not connected to an item'
		);

		return $cases;
	}

	/**
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $entityId ) {
		$namespaceChecker = $this->getMockBuilder( '\Wikibase\NamespaceChecker' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $enabled ) );

		$repoLinker = $this->getMockBuilder( '\Wikibase\RepoLinker' )
			->disableOriginalConstructor()
			->getMock();

		$repoLinker->expects( $this->any() )
			->method( 'buildEntityLink' )
			->will( $this->returnValue( 'https://www.wikidata.org/wiki/Q4' ) );

		$siteLinkLookup = $this->getMockBuilder( '\Wikibase\SiteLinkTable' )
			->disableOriginalConstructor()
			->getMock();

		$siteLinkLookup->expects( $this->any() )
			->method( 'getEntityIdForSiteLink' )
			->will( $this->returnValue( $entityId ) );

		$hookHandler = new InfoActionHookHandler(
			$namespaceChecker,
			$repoLinker,
			$siteLinkLookup,
			'enwiki'
		);

		return $hookHandler;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = Title::newFromText(
			'Cat',
			$this->getDefaultWikitextNS()
		);

		$title->resetArticleID( 538 );

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

}
