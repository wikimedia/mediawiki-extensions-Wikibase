<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use JobQueueGroup;
use Title;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\ChangeModification\DispatchChangeVisibilityNotificationJob;
use Wikibase\Repo\Hooks\ArticleRevisionVisibilitySetHookHandler;

/**
 * @covers \Wikibase\Repo\Hooks\ArticleRevisionVisibilitySetHookHandler
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class ArticleRevisionVisibilitySetHookHandlerTest extends \PHPUnit\Framework\TestCase {

	public function testSchedulesDispatchJob() {
		$namespace = 123;
		$title = Title::makeTitle( $namespace, 'Ignored' );
		$revisionIds = [ 456, 789 ];
		$visibilityChangeMap = [
			456 => [ 'oldBits' => 0, 'newBits' => 1 ],
			789 => [ 'oldBits' => 1, 'newBits' => 0 ],
		];
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->once() )
			->method( 'push' )
			->with( $this->callback(
				function ( DispatchChangeVisibilityNotificationJob $job ) use ( $revisionIds, $visibilityChangeMap, $title ) {
					$params = $job->getParams();
					$this->assertSame( $revisionIds, $params['revisionIds'] );
					$this->assertSame( $visibilityChangeMap, $params['visibilityChangeMap'] );
					$this->assertEquals( $title, $job->getTitle() );
					return true;
				}
			) );
		$handler = new ArticleRevisionVisibilitySetHookHandler(
			$jobQueueGroup,
			$this->newEntityNamespaceLookup( true, $namespace )
		);

		$handler->onArticleRevisionVisibilitySet(
			$title,
			$revisionIds,
			$visibilityChangeMap
		);
	}

	public function testNoopForNonEntityNamespace() {
		$namespace = 123;
		$title = Title::makeTitle( $namespace, 'Ignored' );
		$jobQueueGroup = $this->createMock( JobQueueGroup::class );
		$jobQueueGroup->expects( $this->never() )
			->method( 'push' );
		$handler = new ArticleRevisionVisibilitySetHookHandler(
			$jobQueueGroup,
			$this->newEntityNamespaceLookup( false, $namespace )
		);

		$handler->onArticleRevisionVisibilitySet(
			$title,
			[ 456 ],
			[ 456 => [ 'oldBits' => 0, 'newBits' => 1 ] ]
		);

		$this->addToAssertionCount( 1 );
	}

	private function newEntityNamespaceLookup( bool $returnValue, int $expectedNamespace ): EntityNamespaceLookup {
		$entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$entityNamespaceLookup->method( 'isEntityNamespace' )
			->with( $expectedNamespace )
			->willReturn( $returnValue );

		return $entityNamespaceLookup;
	}

}
