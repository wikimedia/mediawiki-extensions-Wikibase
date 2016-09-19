<?php

namespace Wikibase\Repo\Tests\Hooks;

use IContextSource;
use MediaWiki\Linker\LinkRenderer;
use RequestContext;
use FileBasedSiteLookup;
use Site;
use Title;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Store\Sql\SqlSubscriptionLookup;

/**
 * @covers Wikibase\Repo\Hooks\InfoActionHookHandler
 *
 * @group WikibaseRepo
 * @group InfoActionHookHandler
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle( $expected, $context, $pageInfo, $enabled, $subscriptions, $message ) {
		$hookHandler = $this->newHookHandler( $enabled, $subscriptions, $context );
		$pageInfo = $hookHandler->handle( $context, $pageInfo );

		$this->assertEquals( $expected, $pageInfo, $message );
	}

	public function handleProvider() {
		global $wgArticlePath, $wgServer;

		$url = $wgServer . $wgArticlePath;
		$url = str_replace( '$1', 'en:Special:EntityUsage/', $url );

		$context = $this->getContext();

		$cases = [];

		$cases[] = [
			[
				'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>$url dewiki</li><li>$url enwiki</li></ul>",
					],
				]
			],
			$context, [ 'header-properties' => [] ], true, [ 'dewiki', 'enwiki' ],
			'dewiki and enwiki'
		];

		$cases[] = [
			[ 'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>$url elwiki</li></ul>",
					],
				]
			],
			$context,
			[ 'header-properties' => [] ],
			false,
			[ 'elwiki' ],
			'elwiki'
		];

		$cases[] = [
			[
				'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						$context->msg( 'wikibase-pageinfo-subscription-none' )->escaped()
					]
				]
			],
			$context, [ 'header-properties' => [] ], true, false,
			'no subscription'
		];

		return $cases;
	}

	/**
	 * @param bool $enabled
	 * @param ItemId $entityId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $subscriptions, $context ) {
		$namespaceLookup = new EntityNamespaceLookup( [ Item::ENTITY_TYPE => NS_MAIN ] );

		$subLookup = $this->getMockBuilder( SqlSubscriptionLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$subLookup->expects( $this->any() )
			->method( 'getSubscribers' )
			->will( $this->returnValue( $subscriptions ) );

		$siteLookup = $this->getMockBuilder( FileBasedSiteLookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getSite' ] )
			->getMock();

		$siteLookup->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnCallback( [ $this, 'getSite' ] ) );

		$entityIdLookup = $this->getMockBuilder( PagePropsEntityIdLookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getEntityIdForTitle' ] )
			->getMock();

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnValue( new ItemId( 'Q4' ) ) );

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnValue( false ) );

		$linkRenderer = $this->getMockBuilder( LinkRenderer::class )
			->disableOriginalConstructor()
			->setMethods( [ 'makeLink' ] )
			->getMock();

		$linkRenderer->expects( $this->any() )
			->method( 'makeLink' )
			->will( $this->returnCallback( [ $this, 'makeLink' ] ) );

		$hookHandler = new InfoActionHookHandler(
			$namespaceLookup,
			$subLookup,
			$siteLookup,
			$entityIdLookup,
			$linkRenderer,
			$context
		);

		return $hookHandler;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();

		$title->expects( $this->any() )
			->method( 'exists' )
			->will( $this->returnValue( true ) );

		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->will( $this->returnValue( NS_MAIN ) );

		$title->expects( $this->any() )
			->method( 'getPrefixedText' )
			->will( $this->returnValue( 'Q4' ) );

		$context = new RequestContext();
		$context->setTitle( $title );

		$context->setLanguage( 'en' );

		return $context;
	}

	/**
	 * @return Site
	 */
	public function getSite() {
		$site = new Site();
		$site->addInterwikiId( 'en' );
		return $site;
	}

	/**
	 * @param Title $title
	 * @param string $text
	 *
	 * @return string HTML
	 */
	public function makeLink( Title $title, $text ) {
		return $title->getFullURL() . ' ' . $text;
	}

}
