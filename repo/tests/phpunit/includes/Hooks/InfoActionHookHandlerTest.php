<?php

namespace Wikibase\Repo\Tests\Hooks;

use IContextSource;
use MediaWiki\Linker\LinkRenderer;
use RequestContext;
use FileBasedSiteLookup;
use Title;
use Wikibase\Client\Store\Sql\PagePropsEntityIdLookup;
use Wikibase\Lib\Store\EntityNamespaceLookup;
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
		$context = $this->getContext();

		$cases = [];

		$cases[] = array(
			array(
				'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>dewiki</li><li>enwiki</li></ul>",
					),
				)
			),
			$context, array( 'header-properties' => array() ), true, [ 'dewiki', 'enwiki' ],
			'dewiki and enwiki'
		);

		$cases[] = array(
			array( 'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						"<ul><li>elwiki</li></ul>",
					),
				)
			),
			$context,
			array( 'header-properties' => array() ),
			false,
			[ 'elwiki' ],
			'elwiki'
		);

		$cases[] = array(
			array(
				'header-properties' => array(
					array(
						$context->msg( 'wikibase-pageinfo-subscription' )->escaped(),
						$context->msg( 'wikibase-pageinfo-subscription-none' )->escaped()
					)
				)
			),
			$context, array( 'header-properties' => array() ), true, false,
			'no subscription'
		);

		return $cases;
	}

	/**
	 * @param bool $enabled
	 * @param ItemId $entityId
	 *
	 * @return InfoActionHookHandler
	 */
	private function newHookHandler( $enabled, $subscriptions, $context ) {
		$namespaceLookup = WikibaseRepo::getDefaultInstance()->getEntityNamespaceLookup();

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
			->will( $this->returnValue( false ) );

		$entityIdLookup = $this->getMockBuilder( PagePropsEntityIdLookup::class )
			->disableOriginalConstructor()
			->setMethods( [ 'getEntityIdForTitle' ] )
			->getMock();

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnValue( new ItemId( 'Q4' ) ) );

		$linkRenderer = $this->getMockBuilder( LinkRenderer::class )
			->disableOriginalConstructor()
			->setMethods( [ 'makeLink' ] )
			->getMock();

		$entityIdLookup->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->will( $this->returnValue( false ) );

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

}
