<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;

/**
 * @covers \Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeChangeOpSerializationValidatorTest extends \PHPUnit\Framework\TestCase {

	public function provideNonStringBadgeSerilization() {
		return [
			[ [ 100 ] ],
			[ [ new ItemId( 'Q100' ) ] ],
		];
	}

	/**
	 * @dataProvider provideNonStringBadgeSerilization
	 */
	public function testGivenBadgeSerializationIsNotString_exceptionIsThrown( $serialization ) {
		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$this->createMock( EntityTitleLookup::class ),
			[]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $serialization ) {
				$validator->validateBadgeSerialization( $serialization );
			},
			'not-recognized-string'
		);
	}

	public function provideNonItemIdSerialization() {
		return [
			[ [ '100' ] ],
			[ [ 'AAA' ] ],
			[ [ 'P100' ] ],
		];
	}

	/**
	 * @dataProvider provideNonItemIdSerialization
	 */
	public function testGivenBadgeSerializationIsNotItemIdSerialization_exceptionIsThrown( $serialization ) {
		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$this->createMock( EntityTitleLookup::class ),
			[]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $serialization ) {
				$validator->validateBadgeSerialization( $serialization );
			},
			'invalid-entity-id'
		);
	}

	public function testGivenBadgeNotAnAllowedBadgeItemId_exceptionIsThrown() {
		$validBadgeItemId = 'Q100';
		$invalidBadgeItemId = 'Q200';

		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$this->createMock( EntityTitleLookup::class ),
			[ $validBadgeItemId ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $invalidBadgeItemId ) {
				$validator->validateBadgeSerialization( [ $invalidBadgeItemId ] );
			},
			'not-badge'
		);
	}

	public function testGivenBadgeItemsPageIsNull_exceptionIsThrown() {
		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->willReturn( null );

		$badgeItemId = 'Q100';

		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$titleLookup,
			[ $badgeItemId ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $badgeItemId ) {
				$validator->validateBadgeSerialization( [ $badgeItemId ] );
			},
			'no-such-entity'
		);
	}

	public function testGivenBadgeItemsPageDoesNotExist_exceptionIsThrown() {
		$title = $this->createMock( Title::class );
		$title->method( 'exists' )
			->willReturn( false );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->willReturn( $title );

		$badgeItemId = 'Q100';

		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$titleLookup,
			[ $badgeItemId ]
		);

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $badgeItemId ) {
				$validator->validateBadgeSerialization( [ $badgeItemId ] );
			},
			'no-such-entity'
		);
	}

	public function testGivenValidBadgeSerialization_noExceptionIsThrown() {
		$title = $this->createMock( Title::class );
		$title->method( 'exists' )
			->willReturn( true );

		$titleLookup = $this->createMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->willReturn( $title );

		$badgeItemId = 'Q100';

		$validator = new SiteLinkBadgeChangeOpSerializationValidator(
			$titleLookup,
			[ $badgeItemId ]
		);

		$exception = null;

		try {
			$validator->validateBadgeSerialization( [ $badgeItemId ] );
		} catch ( \Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

}
