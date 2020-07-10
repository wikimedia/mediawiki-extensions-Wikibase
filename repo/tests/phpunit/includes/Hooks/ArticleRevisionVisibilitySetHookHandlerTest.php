<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Hooks;

use IJobSpecification;
use JobQueueGroup;
use MediaWiki\Revision\MutableRevisionRecord;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Revision\RevisionRecord;
use Title;
use TitleFactory;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Store\EntityNamespaceLookup;
use Wikibase\Repo\Content\EntityContentFactory;
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

	/**
	 * @var string|null
	 */
	private static $mwTimestamp;

	public function parameterProvider(): array {
		$repoRevision24IdentifierArray = ( new RepoRevisionIdentifier(
			'Q44801',
			$this->getMwTimestamp(),
			24
		) )->toArray();
		$repoRevision25IdentifierArray = ( new RepoRevisionIdentifier(
			'Q44802',
			$this->getMwTimestamp(),
			25
		) )->toArray();
		$repoRevision26IdentifierArray = ( new RepoRevisionIdentifier(
			'Q44803',
			$this->getMwTimestamp(),
			26
		) )->toArray();
		$visibilityChangeMap = [
			12 =>
				[
					'newBits' => 1,
					'oldBits' => 0,
				],
			];

		return [
			'No jobs dispatched: Propagation disabled' => [
				'expectedJobs' => null,
				'revisionIds' => [ 12 ],
				'visibilityChangeMap' => $visibilityChangeMap,
				'isEntityNamespace' => true,
				'batchSize' => 12,
				'propagateChangeVisibility' => false,
			],
			'No jobs dispatched: Non-entity namespace' => [
				'expectedJobs' => null,
				'revisionIds' => [ 12 ],
				'visibilityChangeMap' => $visibilityChangeMap,
				'isEntityNamespace' => false,
			],
			'No jobs dispatched: Revision to old' => [
				'expectedJobs' => null,
				'revisionIds' => [ 408 ],
				'visibilityChangeMap' => [ 408 => [ 'newBits' => 6, 'oldBits' => 2 ] ],
			],
			'No jobs dispatched: Unknown revision' => [
				'expectedJobs' => null,
				'revisionIds' => [ 404 ],
				'visibilityChangeMap' => [ 404 => [ 'newBits' => 1, 'oldBits' => 0 ] ],
			],
			'One job with one revision identifier dispatched' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision24IdentifierArray ] ),
						'visibilityBitFlag' => 1,
					],
				],
				'revisionIds' => [ 12 ],
				'visibilityChangeMap' => $visibilityChangeMap,
			],
			'One job with two revision identifiers dispatched' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [
							$repoRevision24IdentifierArray,
							$repoRevision25IdentifierArray,
						] ),
						'visibilityBitFlag' => 1,
					],
				],
				'revisionIds' => [ 12, 13 ],
				'visibilityChangeMap' =>
					$visibilityChangeMap + [ 13 => [ 'newBits' => 1, 'oldBits' => 474 ] ],
			],
			'Two jobs dispatched (different bit flags)' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision24IdentifierArray ] ),
						'visibilityBitFlag' => 1,
					],
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision25IdentifierArray ] ),
						'visibilityBitFlag' => 6,
					],
				],
				'revisionIds' => [ 12, 13 ],
				'visibilityChangeMap' =>
					$visibilityChangeMap + [ 13 => [ 'newBits' => 6, 'oldBits' => 2 ] ],
			],
			'Two jobs dispatched (small batch size)' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [
							$repoRevision24IdentifierArray,
							$repoRevision25IdentifierArray,
						] ),
						'visibilityBitFlag' => 1,
					],
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision26IdentifierArray ] ),
						'visibilityBitFlag' => 1,
					],
				],
				'revisionIds' => [ 12, 13, 14 ],
				'visibilityChangeMap' =>
					$visibilityChangeMap + [
						13 => [ 'newBits' => 1, 'oldBits' => 2 ],
						14 => [ 'newBits' => 1, 'oldBits' => 99 ],
					],
				'isEntityNamespace' => true,
				'batchSize' => 2,
			],
			'Multiple jobs with multiple revisions dispatched' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [
							$repoRevision24IdentifierArray,
							$repoRevision26IdentifierArray,
						] ),
						'visibilityBitFlag' => 1,
					],
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision25IdentifierArray ] ),
						'visibilityBitFlag' => 6,
					],
				],
				'revisionIds' => [ 12, 13, 14 ],
				'visibilityChangeMap' =>
					$visibilityChangeMap + [
						13 => [ 'newBits' => 6, 'oldBits' => 2 ],
						14 => [ 'newBits' => 1, 'oldBits' => 99 ],
					],
			],
			'One job dispatched, some revisions discarded' => [
				'expectedJobs' => [
					[
						'revisionIdentifiersJson' => json_encode( [ $repoRevision24IdentifierArray ] ),
						'visibilityBitFlag' => 1,
					],
				],
				// revision 404 doesn't exist and revision 408 is "very old"
				'revisionIds' => [ 404, 12, 408 ],
				'visibilityChangeMap' =>
					$visibilityChangeMap + [
						404 => [ 'newBits' => 1, 'oldBits' => 0 ],
						408 => [ 'newBits' => 1, 'oldBits' => 2 ],
					],
			],
		];
	}

	/**
	 * @dataProvider parameterProvider
	 */
	public function testHandle(
		?array $expectedJobParams,
		array $revisionIds,
		array $visibilityChangeMap,
		bool $isEntityNamespace = true,
		int $batchSize = 3,
		bool $propagateChangeVisibility = true
	) {
		$wikiIds = [ 'dummywiki', 'another_wiki' ];
		$jobQueueGroupFactoryCallCount = 0;

		$handler = new ArticleRevisionVisibilitySetHookHandler(
			$this->newRevisionLookup(),
			$this->newEntityContentFactory(),
			$this->newEntityNamespaceLookup( $isEntityNamespace, 0 ),
			$this->newTitleFactory(),
			$wikiIds,
			$this->newJobQueueGroupFactory( $jobQueueGroupFactoryCallCount, $wikiIds, $expectedJobParams ),
			$propagateChangeVisibility,
			86400,
			$batchSize
		);

		$handler->handle(
			$this->newTitle(),
			$revisionIds,
			$visibilityChangeMap
		);

		$this->assertSame(
			$expectedJobParams !== null ? count( $wikiIds ) : 0,
			$jobQueueGroupFactoryCallCount
		);
	}

	public function testNewFromGlobalState() {
		$this->assertInstanceOf(
			ArticleRevisionVisibilitySetHookHandler::class,
			ArticleRevisionVisibilitySetHookHandler::newFromGlobalState()
		);
	}

	/**
	 * @return string
	 */
	private function getMwTimestamp(): string {
		if ( !self::$mwTimestamp ) {
			self::$mwTimestamp = wfTimestampNow();
		}

		return self::$mwTimestamp;
	}

	private function newJobQueueGroupFactory(
		int &$jobQueueGroupFactoryCallCount,
		array $wikiIds,
		?array $expectedJobParams
	): callable {
		return function ( string $wikiId ) use ( &$jobQueueGroupFactoryCallCount, $wikiIds, $expectedJobParams ) {
			$this->assertSame( $wikiIds[$jobQueueGroupFactoryCallCount++], $wikiId );

			$jobQueueGroup = $this->createMock( JobQueueGroup::class );
			$jobQueueGroup->expects( $this->once() )
				->method( 'push' )
				->willReturnCallback( function ( array $jobs ) use ( $expectedJobParams ) {
					$this->assertContainsOnlyInstancesOf( IJobSpecification::class, $jobs );

					$actualJobParams = array_map( function ( IJobSpecification $job ) {
						return $job->getParams();
					}, $jobs );

					$this->assertSame( $expectedJobParams, $actualJobParams );
				} );

			return $jobQueueGroup;
		};
	}

	private function newEntityContentFactory(): EntityContentFactory {
		$entityContentFactory = $this->createMock( EntityContentFactory::class );
		$entityContentFactory->expects( $this->any() )
			->method( 'getEntityIdForTitle' )
			->willReturnCallback( function ( Title $title ): EntityId {
				return ( new BasicEntityIdParser() )->parse( $title->getText() );
			} );

		return $entityContentFactory;
	}

	private function newTitleFactory(): TitleFactory {
		$titleFactory = $this->createMock( TitleFactory::class );
		$titleFactory->expects( $this->any() )
			->method( 'newFromID' )
			->willReturnCallback( function ( int $id ): Title {
				$title = $this->createMock( Title::class );
				$title->expects( $this->any() )
					->method( 'getText' )
					->willReturn( 'Q' . ( 1000 + $id ) );

				return $title;
			} );

		return $titleFactory;
	}

	private function newRevisionLookup(): RevisionLookup {
		$revisionLookup = $this->createMock( RevisionLookup::class );
		$revisionLookup->expects( $this->any() )
			->method( 'getRevisionById' )
			->willReturnCallback( function ( int $id ): ?RevisionRecord {
				if ( $id === 404 ) {
					// Non-existant revision
					return null;
				}

				$revisionRecord = new MutableRevisionRecord(
					$this->createMock( Title::class )
				);

				$revisionRecord->setTimestamp( $this->getMwTimestamp() );
				if ( $id === 408 ) {
					// Very old revision
					$revisionRecord->setTimestamp( '20050504121300' );
				}
				$revisionRecord->setId( 12 + $id );
				$revisionRecord->setPageId( 43789 + $id );

				return $revisionRecord;
			} );

		return $revisionLookup;
	}

	private function newTitle(): Title {
		$title = $this->createMock( Title::class );
		$title->expects( $this->any() )
			->method( 'getNamespace' )
			->willReturn( 0 );

		return $title;
	}

	private function newEntityNamespaceLookup( bool $returnValue, int $expectedNamespace ): EntityNamespaceLookup {
		$entityNamespaceLookup = $this->createMock( EntityNamespaceLookup::class );
		$entityNamespaceLookup->expects( $this->any() )
			->method( 'isEntityNamespace' )
			->with( $expectedNamespace )
			->willReturn( $returnValue );

		return $entityNamespaceLookup;
	}

}
