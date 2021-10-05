<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Validators;

use DataValues\TimeValue;
use ValueValidators\Result;
use ValueValidators\ValueValidator;

/**
 * Validate a date value, making sure that the day component is not 00 for day-precision values,
 * and that the month component is not 00 for values with precision day or month.
 *
 * It assumes that the input value is the array value of a TimeValue,
 * i.e. it has precision and time fields, with the time in proper timestamp format.
 *
 * @license GPL-2.0-or-later
 * @author Noa Rave
 */
class TimestampPrecisionValidator implements ValueValidator {

	/** @var ValueValidator */
	private $precisionMonthValidator;
	/** @var ValueValidator */
	private $precisionDayValidator;

	public function __construct() {
		// Month should not be 00
		$monthPattern = '/-00-..T/';
		$this->precisionMonthValidator = new RegexValidator( $monthPattern, true );

		// Month or day should not be 00
		$dayPattern = '/-(00-..|..-00)T/';
		$this->precisionDayValidator = new RegexValidator( $dayPattern, true );
	}

	public function validate( $value ): Result {
		if ( $value['precision'] === TimeValue::PRECISION_MONTH ) {
			return $this->precisionMonthValidator->validate( $value['time'] );
		}

		if ( $value['precision'] === TimeValue::PRECISION_DAY ) {
			return $this->precisionDayValidator->validate( $value['time'] );
		}

		return Result::newSuccess();
	}

	/**
	 * @see ValueValidator::setOptions()
	 *
	 * @param array $options
	 *
	 * @codeCoverageIgnore
	 */
	public function setOptions( array $options ) {
		// Do nothing. This method shouldn't even be in the interface.
	}
}
