<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use PHPUnit4And6Compat;
use Title;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0-or-later
 */
class SiteLinkBadgeChangeOpSerializationValidatorTest extends \PHPUnit\Framework\TestCase {
	use PHPUnit4And6Compat;

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
			$this->getMock( EntityTitleLookup::class ),
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
			$this->getMock( EntityTitleLookup::class ),
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
			$this->getMock( EntityTitleLookup::class ),
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
		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->will( $this->returnValue( null ) );

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
		$title = $this->getMock( Title::class );
		$title->method( 'exists' )
			->will( $this->returnValue( false ) );

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->will( $this->returnValue( $title ) );

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
		$title = $this->getMock( Title::class );
		$title->method( 'exists' )
			->will( $this->returnValue( true ) );

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->will( $this->returnValue( $title ) );

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
