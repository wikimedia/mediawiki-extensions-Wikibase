<?php


namespace Wikibase\Repo\Tests\Notifications;

use User;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Changes\EntityChange;
use Wikibase\Lib\Tests\Changes\ChangeRowTest;
use Wikibase\Repo\Notifications\RepoItemChange;

/**
 * Class RepoEntityChangeTest
 * @group Wikibase
 */
class RepoItemChangeTest extends ChangeRowTest {
	private function newItemChange( ItemId $itemId ): RepoItemChange {

		$change = new RepoItemChange();
		$type = 'wikibase-' . $itemId->getEntityType() . '~' . EntityChange::UPDATE;
		$change->setField( 'type', $type );
		return $change;
	}

	public function testSetMetadataFromUser() {
		$user = $this->createMock( User::class );

		$user->expects( $this->atLeastOnce() )
			->method( 'getId' )
			->will( $this->returnValue( 7 ) );

		$user->expects( $this->atLeastOnce() )
			->method( 'getName' )
			->will( $this->returnValue( 'Mr. Kittens' ) );

		$entityChange = $this->newItemChange( new ItemId( 'Q7' ) );

		$entityChange->setMetadata( [
			'user_text' => 'Dobby', // will be overwritten
			'page_id' => 5, // will NOT be overwritten
		] );

		$entityChange->setMetadataFromUser( $user, 3 );

		$this->assertEquals( 7, $entityChange->getField( 'user_id' ), 'user_id' );

		$metadata = $entityChange->getMetadata();
		$this->assertEquals( 'Mr. Kittens', $metadata['user_text'], 'user_text' );
		$this->assertEquals( 5, $metadata['page_id'], 'page_id should be preserved' );
		$this->assertArrayHasKey( 'central_user_id', $metadata, 'central_user_id should be initialized' );
		$this->assertArrayHasKey( 'rev_id', $metadata, 'rev_id should be initialized' );
	}
}
