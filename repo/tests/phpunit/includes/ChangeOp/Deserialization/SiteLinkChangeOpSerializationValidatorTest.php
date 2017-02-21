<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Exception;
use Site;
use SiteList;
use Title;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkBadgeChangeOpSerializationValidator;
use Wikibase\Repo\ChangeOp\Deserialization\SiteLinkChangeOpSerializationValidator;

/**
 * @covers Wikibase\Repo\ChangeOp\Deserialization\SiteLinkChangeOpSerializationValidator
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
class SiteLinkChangeOpSerializationValidatorTest extends \PHPUnit_Framework_TestCase {

	const SITE_ID = 'foowiki';

	const BADGE_ITEM_ID = 'Q123';

	private function newSiteLinkChangeOpSerializationValidator() {
		$title = $this->getMockBuilder( Title::class )
			->disableOriginalConstructor()
			->getMock();
		$title->method( 'exists' )
			->will( $this->returnValue( true ) );

		$titleLookup = $this->getMock( EntityTitleLookup::class );
		$titleLookup->method( $this->anything() )
			->will( $this->returnValue( $title ) );

		return new SiteLinkChangeOpSerializationValidator(
			new SiteLinkBadgeChangeOpSerializationValidator(
				$titleLookup,
				[ self::BADGE_ITEM_ID ]
			)
		);
	}

	public function provideInvalidSerialization() {
		return [
			'serialization is not an array' => [ 'BAD', 'not-recognized-array' ],
			'no site in serialization' => [ [], 'no-site' ],
			'site is not a string' => [ [ 'site' => 1200 ], 'not-recognized-string' ],
			'title is not a string' => [ [ 'site' => self::SITE_ID, 'title' => 4000 ], 'not-recognized-string' ],
			'badges is not an array' => [ [ 'site' => self::SITE_ID, 'badges' => 'BAD' ], 'not-recognized-array' ],
		];
	}

	/**
	 * @dataProvider provideInvalidSerialization
	 */
	public function testGivenInvalidSerialization_exceptionIsThrown( $serialization, $expectedErrorCode ) {
		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $serialization ) {
				$validator->validateSiteLinkSerialization( $serialization, self::SITE_ID );
			},
			$expectedErrorCode
		);
	}

	public function testGivenSiteIsInconsistent_exceptionIsThrown() {
		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator ) {
				$validator->validateSiteLinkSerialization( [ 'site' => 'somewiki' ], self::SITE_ID );
			},
			'inconsistent-site'
		);
	}

	public function testGivenSiteNotInValidSiteList_exceptionIsThrown() {
		$site = new Site();
		$site->setGlobalId( 'someotherwiki' );

		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator, $site ) {
				$validator->validateSiteLinkSerialization(
					[ 'site' => self::SITE_ID ],
					self::SITE_ID,
					new SiteList( $site )
				);
			},
			'not-recognized-site'
		);
	}

	public function testGivenInvalidBadges_exceptionIsThrown() {
		$badgeValidator = $this->getMockBuilder( SiteLinkBadgeChangeOpSerializationValidator::class )
			->disableOriginalConstructor()
			->getMock();
		$badgeValidator->method( $this->anything() )
			->will( $this->throwException(
				new ChangeOpDeserializationException( 'invalid badge serialization', 'test-badge-error' )
			) );

		$validator = new SiteLinkChangeOpSerializationValidator( $badgeValidator );

		ChangeOpDeserializationAssert::assertThrowsChangeOpDeserializationException(
			function() use ( $validator ) {
				$validator->validateSiteLinkSerialization( [ 'site' => self::SITE_ID, 'badges' => [ 'Q200' ] ], self::SITE_ID );
			},
			'test-badge-error'
		);
	}

	public function provideValidSerialization() {
		return [
			'site and title given' => [ [ 'site' => self::SITE_ID, 'title' => 'Some Page' ] ],
			'site and badges given' => [ [ 'site' => self::SITE_ID, 'badges' => [ self::BADGE_ITEM_ID ] ] ],
			'site and title and badges given' => [ [ 'site' => self::SITE_ID, 'title' => 'Some Page', 'badges' => [ self::BADGE_ITEM_ID ] ] ],
			'title is empty string' => [ [ 'site' => self::SITE_ID, 'title' => '' ] ],
			'empty badge list' => [ [ 'site' => self::SITE_ID, 'badges' => [] ] ],
		];
	}

	/**
	 * @dataProvider provideValidSerialization
	 */
	public function testGivenValidSerialization_noExceptionIsThrown( array $serialization ) {
		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		$exception = null;

		try {
			$validator->validateSiteLinkSerialization( $serialization, self::SITE_ID );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

	/**
	 * @dataProvider provideValidSerialization
	 */
	public function testGivenNumericSiteIdAndValidSerialization_noExceptionIsThrown( array $serialization ) {
		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		$exception = null;

		try {
			$validator->validateSiteLinkSerialization( $serialization, 42 );
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

	public function testGivenValidSiteListGivenAndSiteInTheList_noExceptionIsThrown() {
		$site = new Site();
		$site->setGlobalId( self::SITE_ID );

		$validator = $this->newSiteLinkChangeOpSerializationValidator();

		$exception = null;

		try {
			$validator->validateSiteLinkSerialization(
				[ 'site' => self::SITE_ID, 'title' => '' ],
				self::SITE_ID,
				new SiteList( [ $site ] )
			);
		} catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNull( $exception );
	}

}
