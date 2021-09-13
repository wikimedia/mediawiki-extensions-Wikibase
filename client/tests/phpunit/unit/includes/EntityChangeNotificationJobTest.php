<?php

declare( strict_types = 1 );

namespace Wikibase\Client\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Wikibase\Client\Changes\ChangeHandler;
use Wikibase\Client\EntityChangeNotificationJob;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
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
}
