<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\EntityChangeNotificationJob;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Changes\ItemChange;

/**
 * @covers \Wikibase\Client\EntityChangeNotificationJob
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class EntityChangeNotificationJobTest extends TestCase {

	public function testHandlingChange() {
		$testItemId = new ItemId( 'Q1' );
		$testItemChange = new ItemChange( [
			'time' => '20210906122813',
			'info' => [], // some json
			'user_id' => '43',
			'revision_id' => '123',
			'object_id' => 'Q1',
			'type' => 'wikibase-item~update',
		] );
		$testItemChange->setEntityId( $testItemId );

		$mockChangeHandler = $this->createMock( ChangeHandler::class );
		$mockChangeHandler->expects( $this->once() )->method( 'handleChanges' )
			->with(
				[ $testItemChange ],
				[
					'rootJobSignature' => null,
					'rootJobTimestamp' => null,
				]
			);

		$mockEntityIdParser = $this->createMock( EntityIdParser::class );
		$mockEntityIdParser->expects( $this->once() )->method( 'parse' )
			->with( 'Q1' )->willReturn( $testItemId );

		$entityChangeNotificationJob = new EntityChangeNotificationJob(
			$mockChangeHandler,
			$mockEntityIdParser,
			new NullLogger(),
			[
				'changes' => [ $testItemChange->getFields() ],
			]
		);

		$entityChangeNotificationJob->run();
	}

	public function testHandlingBatchChange() {
		# Testing for a batch update scenario for an entity.
		$testItemId = new ItemId( 'Q1' );
		$testItemChange = new ItemChange( [
			'time' => '20210906122813',
			'info' => [], // some json
			'user_id' => '43',
			'revision_id' => '123',
			'object_id' => 'Q1',
			'type' => 'wikibase-item~update',
		] );

		$testStatementChange = new ItemChange( [
			'time' => '20210906122813',
			'info' => [], // some json
			'user_id' => '43',
			'revision_id' => '124',
			'object_id' => 'Q1',
			'type' => 'wikibase-item~update',
		] );
		$testItemChange->setEntityId( $testItemId );
		$testStatementChange->setEntityId( $testItemId );

		$testPropertyId = new NumericPropertyId( 'P18' );
		$testPropertyChange = new EntityChange( [
			'time' => '20210906122813',
			'info' => [], // some json
			'user_id' => '43',
			'revision_id' => '125',
			'object_id' => 'P18',
			'type' => 'wikibase-property~update',
		] );
		$testPropertyChange->setEntityId( $testPropertyId );

		$mockChangeHandler = $this->createMock( ChangeHandler::class );
		$mockChangeHandler->expects( $this->once() )->method( 'handleChanges' )
			->with(
				[ $testItemChange, $testStatementChange, $testPropertyChange ],
				[
					'rootJobSignature' => null,
					'rootJobTimestamp' => null,
				]
			);

		$mockEntityIdParser = $this->createMock( EntityIdParser::class );
		$mockEntityIdParser->expects( $this->exactly( 3 ) )->method( 'parse' )
		->willReturn( $testItemId, $testItemId, $testPropertyId );

		$entityChangeNotificationJob = new EntityChangeNotificationJob(
			$mockChangeHandler,
			$mockEntityIdParser,
			new NullLogger(),
			[
				'changes' => [
					$testItemChange->getFields(),
					$testStatementChange->getFields(),
					$testPropertyChange->getFields(),
				],
			]
		);

		$entityChangeNotificationJob->run();
	}

	public function testHandlingChangeWithEmptyChangesArray() {

		$mockChangeHandler = $this->createMock( ChangeHandler::class );
		$mockChangeHandler->expects( $this->never() )->method( 'handleChanges' );

		$mockEntityIdParser = $this->createMock( EntityIdParser::class );
		$mockEntityIdParser->expects( $this->never() )->method( 'parse' );

		$entityChangeNotificationJob = new EntityChangeNotificationJob(
			$mockChangeHandler,
			$mockEntityIdParser,
			new NullLogger(),
			[
				'changes' => [],
			]
		);

		$entityChangeNotificationJob->run();
	}
}
