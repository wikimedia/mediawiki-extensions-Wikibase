<?php

namespace Wikibase\Repo\Tests\Hooks;

use Content;
use FauxRequest;
use IContextSource;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\SlotRecord;
use MediaWikiIntegrationTestCase;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Content\EntityInstanceHolder;
use Wikibase\Repo\Content\ItemContent;
use Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner;
use Wikibase\Repo\Store\EntityTitleStoreLookup;

/**
 * @covers \Wikibase\Repo\EditEntity\MediawikiEditFilterHookRunner
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Addshore
 */
class EditFilterHookRunnerTest extends MediaWikiIntegrationTestCase {

	public function getEditFilterHookRunner(): MediawikiEditFilterHookRunner {
		$namespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$namespaceLookup->method( 'getEntitySlotRole' )->willReturn( SlotRecord::MAIN );

		$entityTitleLookup = $this->createMock( EntityTitleStoreLookup::class );
		$entityTitleLookup->method( 'getTitleForId' )
			->willReturnCallback( function( EntityId $id ) {
				return Title::newFromTextThrow( $id->getSerialization(), NS_MAIN );
			} );

		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->method( 'newFromEntity' )
			->with( $this->isInstanceOf( EntityDocument::class ) )
			->willReturn( new ItemContent( new EntityInstanceHolder( new Item() ) ) );
		$entityContentFactory->method( 'newFromRedirect' )
			->with( $this->isInstanceOf( EntityRedirect::class ) )
			->willReturn( new ItemContent( new EntityInstanceHolder( new Item() ) ) );

		return new MediawikiEditFilterHookRunner(
			$namespaceLookup,
			$entityTitleLookup,
			$entityContentFactory
		);
	}

	public function testRun_noHooksRegisteredGoodStatus() {
		$this->clearHook( 'EditFilterMergedContent' );

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setUser( User::newFromName( 'EditFilterHookRunnerTestUser' ) );

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			new Item(),
			$context,
			'summary'
		);
		$this->assertTrue( $status->isGood() );
	}

	public function runData() {
		return [
			'good existing item' => [
				Status::newGood(),
				new Item( new ItemId( 'Q444' ) ),
				[
					'status' => Status::newGood(),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				],
			],
			'fatal existing item' => [
				Status::newFatal( 'foo' ),
				new Item( new ItemId( 'Q444' ) ),
				[
					'status' => Status::newFatal( 'foo' ),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				],
			],
			'good new item' => [
				Status::newGood(),
				new Item(),
				[
					'status' => Status::newGood(),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				],
			],
			'fatal new item' => [
				Status::newFatal( 'bar' ),
				new Item(),
				[
					'status' => Status::newFatal( 'bar' ),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				],
			],
			'good existing entityredirect' => [
				Status::newGood(),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				[
					'status' => Status::newGood(),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				],
			],
			'fatal existing entityredirect' => [
				Status::newFatal( 'baz' ),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				[
					'status' => Status::newFatal( 'baz' ),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				],
			],
		];
	}

	/**
	 * @param Status $inputStatus
	 * @param EntityDocument|EntityRedirect|null $new
	 * @param array $expected
	 *
	 * @dataProvider runData
	 */
	public function testRun_hooksAreCalled( Status $inputStatus, $new, array $expected ) {
		$this->clearHook( 'EditFilterMergedContent' );

		$this->setTemporaryHook(
			'EditFilterMergedContent',
			function(
				IContextSource $context,
				Content $content,
				Status $status,
				$summary,
				User $user,
				$minoredit
			) use ( $expected, $inputStatus ) {
				$wikiPage = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $context->getTitle() );
				$this->assertSame( $expected['title'], $context->getTitle()->getFullText() );
				$this->assertSame( $context->getTitle(), $wikiPage->getTitle() );
				$this->assertSame( $expected['namespace'], $context->getTitle()->getNamespace() );
				$this->assertEquals( new ItemContent( new EntityInstanceHolder( new Item() ) ), $content );
				$this->assertTrue( $status->isGood() );
				$this->assertIsString( $summary );
				$this->assertSame( 'EditFilterHookRunnerTestUser', $user->getName() );
				$this->assertIsBool( $minoredit );

				// Change the status
				$status->merge( $inputStatus );
			}
		);

		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );
		$context->setUser( User::newFromName( 'EditFilterHookRunnerTestUser' ) );

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			$new,
			$context,
			'summary'
		);
		$this->assertEquals( $expected['status'], $status );
	}

}
