<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\ChangeModification;

use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\JobQueue\JobQueueGroupFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use Psr\Log\NullLogger;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\Lib\Changes\RepoRevisionIdentifier;
use Wikibase\Lib\Store\EntityIdLookup;
use Wikibase\Repo\ChangeModification\DispatchChangeVisibilityNotificationJob;

/**
 * @covers \Wikibase\Repo\ChangeModification\DispatchChangeVisibilityNotificationJob
 *
 * @group Wikibase
 * @group Database
 *
 * @license GPL-2.0-or-later
 */
class DispatchChangeVisibilityNotificationJobTest extends MediaWikiIntegrationTestCase {

	/**
	 * @var string|null
	 */
	private static $mwTimestamp;

	public static function parameterProvider(): array {
		$repoRevision24IdentifierArray = ( new RepoRevisionIdentifier(
			'Q1042',
			self::getMwTimestamp(),
			12
		) )->toArray();
		$repoRevision25IdentifierArray = ( new RepoRevisionIdentifier(
			'Q1042',
			self::getMwTimestamp(),
			13
		) )->toArray();
		$repoRevision26IdentifierArray = ( new RepoRevisionIdentifier(
			'Q1042',
			self::getMwTimestamp(),
			14
		) )->toArray();
		$visibilityChangeMap = [
			12 =>
				[
					'newBits' => 1,
					'oldBits' => 0,
				],
		];

		return [
			'No jobs dispatched: Revision to old' => [
				'expectedJobs' => [],
				'revisionIds' => [ 408 ],
				'visibilityChangeMap' => [ 408 => [ 'newBits' => 6, 'oldBits' => 2 ] ],
			],
			'No jobs dispatched: Unknown revision' => [
				'expectedJobs' => [],
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
		array $expectedJobParams,
		array $revisionIds,
		array $visibilityChangeMap,
		int $batchSize = 3
	) {
		$wikiIds = [ 'dummywiki', 'another_wiki' ];
		$actualJobs = [];

		$this->mergeMwGlobalArrayValue( 'wgWBRepoSettings', [
			'localClientDatabases' => $wikiIds,
			'deleteNotificationClientRCMaxAge' => 86400,
			'changeVisibilityNotificationJobBatchSize' => $batchSize,
		] );
		$this->setService( 'RevisionLookup', $this->newRevisionLookup() );

		$job = new DispatchChangeVisibilityNotificationJob(
			$this->newTitle( 42 ),
			[
				'revisionIds' => $revisionIds,
				'visibilityChangeMap' => $visibilityChangeMap,
			]
		);
		$job->initServices(
			$this->newEntityIdLookup(),
			new NullLogger(),
			$this->newJobQueueGroupFactory( $actualJobs )
		);
		$job->run();

		foreach ( $wikiIds as $wikiId ) {
			$this->assertArrayContains(
				$expectedJobParams,
				$actualJobs[$wikiId] ?? [],
				"jobs for $wikiId"
			);
		}
	}

	private static function getMwTimestamp(): string {
		if ( !self::$mwTimestamp ) {
			self::$mwTimestamp = wfTimestampNow();
		}

		return self::$mwTimestamp;
	}

	private function newJobQueueGroupFactory( array &$actualJobs ): JobQueueGroupFactory {
		$jobQueueGroupFactory = $this->createMock( JobQueueGroupFactory::class );
		$jobQueueGroupFactory->method( 'makeJobQueueGroup' )
			->willReturnCallback( function ( string $wikiId ) use ( &$actualJobs ) {
				$jobQueueGroup = $this->createMock( JobQueueGroup::class );
				$jobQueueGroup->method( 'push' )
					->willReturnCallback( function ( array $jobs ) use ( $wikiId, &$actualJobs ) {
						foreach ( $jobs as $job ) {
							$actualJobs[$wikiId][] = $job->getParams();
						}
					} );

				return $jobQueueGroup;
			} );

		return $jobQueueGroupFactory;
	}

	private function newEntityIdLookup(): EntityIdLookup {
		$entityIdLookup = $this->createMock( EntityIdLookup::class );
		$entityIdLookup->expects( $this->once() )
			->method( 'getEntityIdForTitle' )
			->willReturnCallback( function ( Title $title ): EntityId {
				return ( new BasicEntityIdParser() )->parse( $title->getText() );
			} );

		return $entityIdLookup;
	}

	private function newRevisionLookup(): RevisionLookup {
		$revisionLookup = $this->createMock( RevisionLookup::class );
		$revisionLookup->method( 'getTimestampFromId' )
			->willReturnCallback( function ( int $id ) {
				if ( $id === 404 ) {
					// Non-existant revision
					return false;
				}

				if ( $id === 408 ) {
					// Very old revision
					return '20050504121300';
				}

				return $this->getMwTimestamp();
			} );

		return $revisionLookup;
	}

	private function newTitle( int $id ): Title {
		$title = $this->createMock( Title::class );
		$title->method( 'getNamespace' )
			->willReturn( 0 );
		$title->method( 'getDBkey' )
			->willReturn( 'Q' . ( 1000 + $id ) );
		$title->method( 'getArticleId' )
			->willReturn( $id );
		return $title;
	}

}
