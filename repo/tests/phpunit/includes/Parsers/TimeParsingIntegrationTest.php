<?php

declare( strict_types = 1 );

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use PHPUnit\Framework\TestCase;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\TimeParserFactory;

/**
 * This class is designed to test the parsing of date values as close to production as possible, while still being fast.
 *
 * @coversNothing
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0-or-later
 */
class TimeParsingIntegrationTest extends TestCase {

	private function newTimeParserFactory(
		string $languageCode
	): TimeParserFactory {
		$options = new ParserOptions();
		$options->setOption( ValueParser::OPT_LANG, $languageCode );

		return new TimeParserFactory(
			$options,
		);
	}

	/**
	 * @dataProvider validInputProvider
	 */
	public function testParse( string $value, TimeValue $expected, string $languageCode ): void {
		$factory = $this->newTimeParserFactory( $languageCode );
		$parser = $factory->getTimeParser();
		$actual = $parser->parse( $value );

		$this->assertSame( $expected->getArrayValue(), $actual->getArrayValue() );
	}

	public function validInputProvider(): iterable {
		$gregorian = TimeValue::CALENDAR_GREGORIAN;
		$day = TimeValue::PRECISION_DAY;

		$valid = [
			'cs' => [
				// regression tests for T221097
				'8.3.1995' => [ '+1995-03-08T00:00:00Z', $day, $gregorian ],
				'08.03.1995' => [ '+1995-03-08T00:00:00Z', $day, $gregorian ],
				'5.6.1995' => [ '+1995-06-05T00:00:00Z', $day, $gregorian ],
				'05.06.1995' => [ '+1995-06-05T00:00:00Z', $day, $gregorian ],
				'11.12.2023' => [ '+2023-12-11T00:00:00Z', $day, $gregorian ],
				'8.3.2023' => [ '+2023-03-08T00:00:00Z', $day, $gregorian ],
				'08.03.2023' => [ '+2023-03-08T00:00:00Z', $day, $gregorian ],
				'1.2.2023' => [ '+2023-02-01T00:00:00Z', $day, $gregorian ],
				'01.02.2023' => [ '+2023-02-01T00:00:00Z', $day, $gregorian ],
			],
		];

		foreach ( $valid as $languageCode => $cases ) {
			foreach ( $cases as $value => $expected ) {
				yield $languageCode . ' ' . $value => [
					$value,
					new TimeValue( $expected[0], 0, 0, 0, $expected[1], $expected[2] ),
					$languageCode,
				];
			}
		}
	}
}
