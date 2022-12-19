<?php

namespace Wikibase\Repo\Tests\Hooks;

use Html;
use IContextSource;
use PageProps;
use RequestContext;
use Site;
use SiteLookup;
use Title;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Hooks\InfoActionHookHandler;
use Wikibase\Repo\Store\SubscriptionLookup;

/**
 * @covers \Wikibase\Repo\Hooks\InfoActionHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
class InfoActionHookHandlerTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider handleProvider
	 */
	public function testHandle(
		array $expected,
		IContextSource $context,
		array $subscriptions
	) {
		$hookHandler = $this->newHookHandler( $subscriptions, $context );
		$pageInfo = $hookHandler->handle( $context, [ 'header-basic' => [] ] );

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
				[
					'header-properties' => [
						[
							'(wikibase-pageinfo-subscription)',
							"<ul><li>$elementDewiki</li><li>$elementEnwiki</li></ul>",
						],
					],
					'header-basic' => [
						[ '(wikibase-pageinfo-wb-claims)', '5' ],
						[ '(wikibase-pageinfo-wb-identifiers)', '4' ],
					],
				],
				$context,
				[ 'dewiki', 'enwiki' ],
			],
			'elwiki' => [
				[
					'header-properties' => [
						[
							'(wikibase-pageinfo-subscription)',
							"<ul><li>$elementElwiki</li></ul>",
						],
					],
					'header-basic' => [
						[ '(wikibase-pageinfo-wb-claims)', '5' ],
						[ '(wikibase-pageinfo-wb-identifiers)', '4' ],
					],
				],
				$context,
				[ 'elwiki' ],
			],
			'no subscription' => [
				[
					'header-properties' => [
						[
							'(wikibase-pageinfo-subscription)',
							'(wikibase-pageinfo-subscription-none)',
						],
					],
					'header-basic' => [
						[ '(wikibase-pageinfo-wb-claims)', '5' ],
						[ '(wikibase-pageinfo-wb-identifiers)', '4' ],
					],
				],
				$context,
				[],
			],
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

		$subLookup = $this->createMock( SubscriptionLookup::class );
		$subLookup->expects( $this->once() )
			->method( 'getSubscribers' )
			->with( $itemId )
			->willReturn( $subscriptions );

		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->with( $context->getTitle() )
			->willReturn( $itemId );

		$pageProps = $this->createMock( PageProps::class );
		$pageProps->expects( $this->once() )
			->method( 'getProperties' )
			->with( $context->getTitle() )
			->willReturn( [ 1234 => [ 'wb-claims' => 5, 'wb-identifiers' => 4 ] ] );

		return new InfoActionHookHandler(
			new EntityNamespaceLookup( [ Item::ENTITY_TYPE => NS_MAIN ] ),
			$subLookup,
			$this->newSiteLookup(),
			$entityIdLookup,
			$context,
			$pageProps
		);
	}

	/**
	 * @return SiteLookup
	 */
	private function newSiteLookup() {
		$site = new Site();
		$site->addInterwikiId( 'en' );
		$site->setLinkPath( 'https://en.wikipedia.org/wiki/$1' );

		$siteLookup = $this->createMock( SiteLookup::class );

		$siteLookup->method( 'getSite' )
			->willReturn( $site );

		return $siteLookup;
	}

	/**
	 * @return IContextSource
	 */
	private function getContext() {
		$title = $this->createMock( Title::class );

		$title->method( 'exists' )
			->willReturn( true );

		$title->method( 'getNamespace' )
			->willReturn( NS_MAIN );

		$title->method( 'getPrefixedText' )
			->willReturn( 'Q4' );

		$context = new RequestContext();
		$context->setTitle( $title );
		$context->setLanguage( 'qqx' );

		return $context;
	}

}
