<?php

namespace Wikibase\Repo\Tests\Hooks;

use Html;
use IContextSource;
use RequestContext;
use SiteLookup;
use Site;
use Title;
use Wikibase\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Store\SubscriptionLookup;

/**
 * @covers Wikibase\Repo\Hooks\InfoActionHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class InfoActionHookHandlerTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle(
		array $expected,
		IContextSource $context,
		array $subscriptions
	) {
		$hookHandler = $this->newHookHandler( $subscriptions, $context );
		$pageInfo = $hookHandler->handle( $context, [] );

		$this->assertEquals( $expected, $pageInfo );
	}

	public function handleProvider() {
		$url = 'https://en.wikipedia.org/wiki/Special%3AEntityUsage%2F';
		$elementDewiki = Html::element( 'a', [ 'href' => $url ], 'dewiki' );
		$elementEnwiki = Html::element( 'a', [ 'href' => $url ], 'enwiki' );
		$elementElwiki = Html::element( 'a', [ 'href' => $url ], 'elwiki' );
		$context = $this->getContext();

		return [
			'dewiki and enwiki' => [
				[ 'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>$elementDewiki</li><li>$elementEnwiki</li></ul>",
					],
				] ],
				$context,
				[ 'dewiki', 'enwiki' ]
			],
			'elwiki' => [
				[ 'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>$elementElwiki</li></ul>",
					],
				] ],
				$context,
				[ 'elwiki' ]
			],
			'no subscription' => [
				[ 'header-properties' => [
					[
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						$context->msg( 'wikibase-pageinfo-subscription-none' )->escaped()
					]
				] ],
				$context,
				[]
			]
		];
	}

	/**
	 * @param string[] $subscriptions
	 * @param IContextSource $context
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( array $subscriptions, IContextSource $context ) {
		$itemId = new ItemId( 'Q4' );

		$subLookup = $this->getMock( SubscriptionLookup::class );
		$subLookup->expects( $this->once() )
			->method( 'getSubscribers' )
			->with( $itemId )
			->will( $this->returnValue( $subscriptions ) );

		$entityIdLookup = $this->getMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $context->getTitle() )
			->will( $this->returnValue( $itemId ) );

		return new InfoActionHookHandler(
			new EntityNamespaceLookup( [ Item::ENTITY_TYPE => NS_MAIN ] ),
			$subLookup,
			$this->newSiteLookup(),
			$entityIdLookup,
			$context
		);
	}

	/**
	 * @return SiteLookup
	 */
	private function newSiteLookup() {
		$site = new Site();
		$site->addInterwikiId( 'en' );
		$site->setLinkPath( 'https://en.wikipedia.org/wiki/$1' );

		$siteLookup = $this->getMock( SiteLookup::class );

		$siteLookup->expects( $this->any() )
			->method( 'getSite' )
			->will( $this->returnValue( $site ) );

		return $siteLookup;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->getMock( Title::class );

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

}
