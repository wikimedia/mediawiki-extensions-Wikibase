<?php

namespace Wikibase\Client\Tests\Hooks;

use IContextSource;
use RequestContext;
use Wikibase\Client\Hooks\InfoActionHookHandler;
use Wikibase\DataModel\Entity\ItemId;

/**
 * @covers Wikibase\Client\Hooks\InfoActionHookHandler
 *
 * @group WikibaseClient
 * @group InfoActionHookHandler
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

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
						$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
						'https://www.wikidata.org/wiki/Q4'
					)
				)
			),
			$context, array( 'header-basic' => array() ), true, new ItemId( 'Q4' ),
			'item id link'
		);

		$cases[] = array(
			array( 'header-basic' => array() ),
			$context,
			array( 'header-basic' => array() ),
			false,
			new ItemId( 'Q4' ),
			'namespace does not have wikibase enabled'
		);

		$cases[] = array(
			array(
				'header-basic' => array(
					array(
						$context->msg( 'wikibase-pageinfo-entity-id' )->escaped(),
						$context->msg( 'wikibase-pageinfo-entity-id-none' )->escaped()
					)
				)
			),
			$context, array( 'header-basic' => array() ), true, false,
			'page is not connected to an item'
		);

		return $cases;
	}

	/**
	 * @param bool $enabled
	 * @param ItemId $entityId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $entityId ) {
		$namespaceChecker = $this->getMockBuilder( '\Wikibase\NamespaceChecker' )
			->disableOriginalConstructor()
			->getMock();

		$namespaceChecker->expects( $this->any() )
			->method( 'isWikibaseEnabled' )
			->will( $this->returnValue( $enabled ) );

		$repoLinker = $this->getMockBuilder( '\Wikibase\Client\RepoLinker' )
			->disableOriginalConstructor()
			->getMock();

		$repoLinker->expects( $this->any() )
			->method( 'buildEntityLink' )
			->will( $this->returnValue( 'https://www.wikidata.org/wiki/Q4' ) );

		$siteLinkLookup = $this->getMockBuilder( 'Wikibase\Lib\Store\SiteLinkTable' )
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
		$title = $this->getMockBuilder( 'Title' )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getFullText' )
			->will( $this->returnValue( 'Cat' ) );

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

}
