<?php

namespace Wikibase\Repo\Tests\Hooks;

use Content;
use FauxRequest;
use IContextSource;
use RequestContext;
use Status;
use Title;
use User;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityRedirect;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\ItemContent;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\Content\EntityContentFactory;
use Wikibase\Repo\Hooks\EditFilterHookRunner;

/**
 * @covers Wikibase\Repo\Hooks\EditFilterHookRunner
 *
 * @since 0.5
 *
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Addshore
 */
class EditFilterHookRunnerTest extends \MediaWikiTestCase {

	/**
	 * @return EditFilterHookRunner
	 */
	public function getEditFilterHookRunner() {
		$context = new RequestContext();
		$context->setRequest( new FauxRequest() );

		$entityTitleLookup = $this->getMock( EntityTitleLookup::class );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getTitleForId' )
			->will( $this->returnCallback( function( EntityId $id ) {
				return Title::newFromText( $id->getSerialization(), NS_MAIN );
			} ) );
		$entityTitleLookup->expects( $this->any() )
			->method( 'getNamespaceForType' )
			->will( $this->returnValue( NS_MAIN ) );

		$entityContentFactory = $this->getMockBuilder( EntityContentFactory::class )
			->disableOriginalConstructor()
			->getMock();
		$entityContentFactory->expects( $this->any() )
			->method( 'newFromEntity' )
			->with( $this->isInstanceOf( EntityDocument::class ) )
			->will( $this->returnValue( ItemContent::newEmpty() ) );
		$entityContentFactory->expects( $this->any() )
			->method( 'newFromRedirect' )
			->with( $this->isInstanceOf( EntityRedirect::class ) )
			->will( $this->returnValue( ItemContent::newEmpty() ) );

		return new EditFilterHookRunner(
			$entityTitleLookup,
			$entityContentFactory,
			$context
		);
	}

	public function testRun_noHooksRegisteredGoodStatus() {
		$this->mergeMwGlobalArrayValue( 'wgHooks', array( 'EditFilterMergedContent' => [] ) );

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			new Item(),
			User::newFromName( 'EditFilterHookRunnerTestUser' ),
			'summary'
		);
		$this->assertTrue( $status->isGood() );
	}

	public function runData() {
		return array(
			'good existing item' => array(
				Status::newGood(),
				new Item( new ItemId( 'Q444' ) ),
				array(
					'status' => Status::newGood(),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				)
			),
			'fatal existing item' => array(
				Status::newFatal( 'foo' ),
				new Item( new ItemId( 'Q444' ) ),
				array(
					'status' => Status::newFatal( 'foo' ),
					'title' => 'Q444',
					'namespace' => NS_MAIN,
				)
			),
			'good new item' => array(
				Status::newGood(),
				new Item(),
				array(
					'status' => Status::newGood(),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				)
			),
			'fatal new item' => array(
				Status::newFatal( 'bar' ),
				new Item(),
				array(
					'status' => Status::newFatal( 'bar' ),
					'title' => 'NewItem',
					'namespace' => NS_MAIN,
				)
			),
			'good existing entityredirect' => array(
				Status::newGood(),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				array(
					'status' => Status::newGood(),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				)
			),
			'fatal existing entityredirect' => array(
				Status::newFatal( 'baz' ),
				new EntityRedirect( new ItemId( 'Q12' ), new ItemId( 'Q13' ) ),
				array(
					'status' => Status::newFatal( 'baz' ),
					'title' => 'Q12',
					'namespace' => NS_MAIN,
				)
			),
		);
	}

	/**
	 * @param Status $inputStatus
	 * @param EntityDocument|EntityRedirect|null $new
	 * @param array $expected
	 *
	 * @dataProvider runData
	 */
	public function testRun_hooksAreCalled( Status $inputStatus, $new, array $expected ) {
		$hooks = array_merge(
			$GLOBALS['wgHooks'],
			array( 'EditFilterMergedContent' => [] )
		);

		$hooks['EditFilterMergedContent'][] =
			function(
				IContextSource $context,
				Content $content,
				Status $status,
				$summary,
				User $user,
				$minoredit
			) use ( $expected, $inputStatus )
			{
				$this->assertEquals( $expected['title'], $context->getTitle()->getFullText() );
				$this->assertSame( $context->getTitle(), $context->getWikiPage()->getTitle() );
				$this->assertEquals( $expected['namespace'], $context->getTitle()->getNamespace() );
				$this->assertEquals( ItemContent::newEmpty(), $content );
				$this->assertTrue( $status->isGood() );
				$this->assertTrue( is_string( $summary ) );
				$this->assertEquals( 'EditFilterHookRunnerTestUser', $user->getName() );
				$this->assertTrue( is_bool( $minoredit ) );

				//Change the status
				$status->merge( $inputStatus );
			};

		$this->setMwGlobals( array(
			'wgHooks' => $hooks
		) );

		$runner = $this->getEditFilterHookRunner();
		$status = $runner->run(
			$new,
			User::newFromName( 'EditFilterHookRunnerTestUser' ),
			'summary'
		);
		$this->assertEquals( $expected['status'], $status );
	}

}
