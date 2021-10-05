<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Validators;

use DataValues\TimeValue;
use PHPUnit\Framework\TestCase;
use Wikibase\Repo\Validators\TimestampPrecisionValidator;

/**
 * @covers \Wikibase\Repo\Validators\TimestampPrecisionValidator
 *
 * @group Wikibase
 * @group WikibaseContent
 *
 * @license GPL-2.0-or-later
 */
class TimestampPrecisionValidatorTest extends TestCase {

	public function provideValid(): iterable {
		yield 'day precision, 09-16' => [ [
			'precision' => TimeValue::PRECISION_DAY,
			'time' => '+2015-09-16T00:00:00Z',
		] ];
		yield 'month precision, 09-16' => [ [
			'precision' => TimeValue::PRECISION_MONTH,
			'time' => '+2015-09-16T00:00:00Z',
		] ];
		yield 'month precision, 02-00' => [ [
			'precision' => TimeValue::PRECISION_MONTH,
			'time' => '+2020-02-00T00:00:00Z',
		] ];
		yield 'year precision, 00-00' => [ [
			'precision' => TimeValue::PRECISION_YEAR,
			'time' => '+2020-00-00T00:00:00Z',
		] ];
	}

	public function provideInvalid(): iterable {
		yield 'day precision, 00-00' => [ [
			'precision' => TimeValue::PRECISION_DAY,
			'time' => '+2015-00-00T00:00:00Z',
		] ];
		yield 'day precision, 01-00' => [ [
			'precision' => TimeValue::PRECISION_DAY,
			'time' => '+2015-01-00T00:00:00Z',
		] ];
		yield 'day precision, 00-01' => [ [
			'precision' => TimeValue::PRECISION_DAY,
			'time' => '+2015-00-01T00:00:00Z',
		] ];
		yield 'month precision, 00-00' => [ [
			'precision' => TimeValue::PRECISION_MONTH,
			'time' => '+2020-00-00T00:00:00Z',
		] ];
		yield 'month precision, 00-01' => [ [
			'precision' => TimeValue::PRECISION_MONTH,
			'time' => '+2020-00-01T00:00:00Z',
		] ];
	}

	/** @dataProvider provideValid */
	public function testValid( array $value ): void {
		$validator = new TimestampPrecisionValidator();
		$result = $validator->validate( $value );

		$this->assertTrue( $result->isValid(), 'isValid' );
	}

	/** @dataProvider provideInvalid */
	public function testInvalid( array $value ): void {
		$validator = new TimestampPrecisionValidator();
		$result = $validator->validate( $value );

		$this->assertFalse( $result->isValid(), 'isValid' );
	}

}
