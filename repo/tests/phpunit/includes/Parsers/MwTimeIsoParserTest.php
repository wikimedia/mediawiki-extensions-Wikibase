<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\DataValue;
use DataValues\TimeValue;
use Language;
use MediaWiki\Languages\LanguageFactory;
use MediaWiki\MediaWikiServices;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\MwTimeIsoParser;

/**
 * @covers \Wikibase\Repo\Parsers\MwTimeIsoParser
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0-or-later
 */
class MwTimeIsoParserTest extends \PHPUnit\Framework\TestCase {

	/** @var LanguageFactory */
	private $oldLangFactory;

	/**
	 * @see ValueParserTestBase::getInstance
	 *
	 * @return MwTimeIsoParser
	 */
	protected function getInstance() {
		$options = new ParserOptions();
		$options->setOption( ValueParser::OPT_LANG, 'es' );

		return new MwTimeIsoParser( $options );
	}

	protected function setUp(): void {
		parent::setUp();

		$services = MediaWikiServices::getInstance();
		$this->oldLangFactory = $services->getLanguageFactory();
		$stub = $this->createMock( LanguageFactory::class );
		$stub->method( 'getLanguage' )->willReturnCallback( function ( $code ) {
			return $code === 'es' ? $this->getLanguage() : new \LanguageEn();
		} );
		$services->disableService( 'LanguageFactory' );
		$services->redefineService( 'LanguageFactory',
			function () use ( $stub ) {
				return $stub;
			}
		);
	}

	protected function tearDown(): void {
		MediaWikiServices::getInstance()->resetServiceForTesting( 'LanguageFactory' );
		MediaWikiServices::getInstance()->redefineService(
			'LanguageFactory',
			function () {
				return $this->oldLangFactory;
			}
		);
		parent::tearDown();
	}

	private function getLanguage() {
		$lang = $this->createMock( Language::class );

		$lang->method( 'getCode' )
			->willReturn( 'es' );

		$lang->method( 'parseFormattedNumber' )
			->willReturnCallback( function ( $number ) {
				return ( new \LanguageEn() )->parseFormattedNumber( $number );
			} );

		$lang->method( 'getMessage' )
			->with( $this->isType( 'string' ) )
			->willReturnCallback( function ( $msg ) {
				return $this->getMessages()[$msg] ?? 'kitten';
			} );

		return $lang;
	}

	/**
	 * @return string[]
	 */
	private function getMessages() {
		return [
			// Trivial case
			'wikibase-time-precision-Gannum' => '$1 precision-Gannum',
			// With separate PLURAL case
			'wikibase-time-precision-Mannum' => '$1 {{PLURAL:$1|one|more|evenmore}} precision-Mannum',
			// From the Ukrainian translation
			'wikibase-time-precision-BCE-Mannum' => '$1 мільйон{{PLURAL:$1||ів|и}} років до н.е.',
			// With $1 in the PLURAL case
			'wikibase-time-precision-BCE-century' => '{{PLURAL:$1|$1 one|$1 more|$1 evenmore}} precision-BCE-century',
			// With tags
			'wikibase-time-precision-century' => '$1<sup>e{{PLURAL:$1|r|}}</sup> siècle',
			// A random template in the message
			'wikibase-time-precision-10annum' => '$1 precision-10annum{{PLURAL:$1||s}} {{dummy|1|2|3}}',

			// Invalid messages
			'wikibase-time-precision-BCE-10annum' => '$1',
			'wikibase-time-precision-BCE-millennium' => '-',
			'wikibase-time-precision-BCE-annum' => '',
			'wikibase-time-precision-BCE-Gannum' => null,
		];
	}

	/**
	 * @inheritDoc
	 */
	public function validInputProvider() {
		$gregorian = 'http://www.wikidata.org/entity/Q1985727';
		$julian = 'http://www.wikidata.org/entity/Q1985786';

		$valid = [
			// + dates
			'13 billion years CE' =>
				[ '+13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'23 precision-Gannum' =>
				[ '+23000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'130 billion years CE' =>
				[ '+130000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'13000 billion years CE' =>
				[ '+13000000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'13,000 billion years CE' =>
				[ '+13000000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'13,000 million years CE' =>
				[ '+13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G ],
			'13,800 million years CE' =>
				[ '+13800000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M ],
			'100 million years CE' =>
				[ '+100000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M ],
			'70 million years CE' =>
				[ '+70000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M ],
			'77 million years CE' =>
				[ '+77000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'55 one precision-Mannum' =>
				[ '+55000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'23 more precision-Mannum' =>
				[ '+23000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'21 evenmore precision-Mannum' =>
				[ '+21000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'13 million years CE' =>
				[ '+13000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'1 million years CE' =>
				[ '+1000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M ],
			'100000 years CE' =>
				[ '+100000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K ],
			'100,000 years CE' =>
				[ '+100000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K ],
			'10000 years CE' =>
				[ '+10000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K ],
			'99000 years CE' =>
				[ '+99000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ],
			'99,000 years CE' =>
				[ '+99000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ],
			'5. millennium' =>
				[ '+5000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ],
			'55. millennium' =>
				[ '+55000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K ],
			'10. century' =>
				[ '+1000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'12. century' =>
				[ '+1200-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'9e siècle' =>
				[ '+900-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'9<sup>e</sup> siècle' =>
				[ '+900-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'1980s' =>
				[ '+1980-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ],
			'1990 precision-10annum {{dummy|1|2|3}}' =>
				[ '+1990-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ],
			'2000s' =>
				[ '+2000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ],
			'2010 precision-10annums {{dummy|1|2|3}}' =>
				[ '+2010-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10 ],
			'10s' =>
				[ '+0010-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],
			'12s' =>
				[ '+0012-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],

			// - dates
			'13 billion years BCE' =>
				[ '-13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ],
			'130 billion years BCE' =>
				[ '-130000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ],
			'13000 billion years BCE' =>
				[ '-13000000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ],
			'13,000 billion years BCE' =>
				[ '-13000000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ],
			'13,000 million years BCE' =>
				[ '-13000000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1G, $julian ],
			'13,800 million years BCE' =>
				[ '-13800000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M, $julian ],
			'100 million years BCE' =>
				[ '-100000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100M, $julian ],
			'70 million years BCE' =>
				[ '-70000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10M, $julian ],
			'77 million years BCE' =>
				[ '-77000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ],
			'64 мільйони років до н.е.' =>
				[ '-64000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ],
			'13 million years BCE' =>
				[ '-13000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ],
			'1 million years BCE' =>
				[ '-1000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ],
			'64 мільйон років до н.е.' =>
				[ '-64000000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1M, $julian ],
			'100000 years BCE' =>
				[ '-100000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K, $julian ],
			'100,000 years BCE' =>
				[ '-100000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100K, $julian ],
			'10000 years BCE' =>
				[ '-10000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10K, $julian ],
			'99000 years BCE' =>
				[ '-99000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'99,000 years BCE' =>
				[ '-99000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'5. millennium BCE' =>
				[ '-5000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'55. millennium BCE' =>
				[ '-55000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'22 more precision-BCE-century' =>
				[ '-2200-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'8 evenmore precision-BCE-century' =>
				[ '-0800-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'11 more precision-BCE-century' =>
				[ '-1100-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'10. century BCE' =>
				[ '-1000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'12. century BCE' =>
				[ '-1200-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'10s BCE' =>
				[ '-0010-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],
			'12s BCE' =>
				[ '-0012-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],
			// also parse BC
			'5. millennium BC' =>
				[ '-5000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'55. millennium BC' =>
				[ '-55000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR1K, $julian ],
			'10. century BC' =>
				[ '-1000-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'12. century BC' =>
				[ '-1200-00-00T00:00:00Z', TimeValue::PRECISION_YEAR100, $julian ],
			'10s BC' =>
				[ '-0010-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],
			'12s BC' =>
				[ '-0012-00-00T00:00:00Z', TimeValue::PRECISION_YEAR10, $julian ],
		];

		foreach ( $valid as $value => $expected ) {
			$timestamp = $expected[0];
			$precision = $expected[1];
			$calendarModel = $expected[2] ?? $gregorian;

			yield [
				(string)$value,
				new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel ),
			];
		}
	}

	/**
	 * @inheritDoc
	 */
	public function invalidInputProvider() {

		$invalid = [
			//These are just wrong!
			'June June June',
			'111 111 111',
			'Jann 2014',

			//Not within the scope of this parser
			'200000000',
			'1 June 2013',
			'June 2013',
			'2000',
			'1980x',
			'1980ss',
		];

		foreach ( $invalid as $value ) {
			yield [ $value ];
		}
	}

	public function testSetAndGetOptions() {

		$parser = $this->getInstance();

		$parser->setOptions( new ParserOptions() );

		$this->assertEquals( new ParserOptions(), $parser->getOptions() );

		$options = new ParserOptions();
		$options->setOption( 'someoption', 'someoption' );

		$parser->setOptions( $options );

		$this->assertEquals( $options, $parser->getOptions() );
	}

	/**
	 * @since 0.1
	 *
	 * @dataProvider validInputProvider
	 * @param mixed $value
	 * @param mixed $expected
	 * @param ValueParser|null $parser
	 */
	public function testParseWithValidInputs( $value, $expected, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->assertSmartEquals( $expected, $parser->parse( $value ) );
	}

	/**
	 * @param DataValue|mixed $expected
	 * @param DataValue|mixed $actual
	 */
	private function assertSmartEquals( $expected, $actual ) {
		if ( $this->requireDataValue() ) {
			if ( $expected instanceof DataValue && $actual instanceof DataValue ) {
				$msg = "testing equals():\n"
					. preg_replace( '/\s+/', ' ', print_r( $actual->toArray(), true ) ) . " should equal\n"
					. preg_replace( '/\s+/', ' ', print_r( $expected->toArray(), true ) );
			} else {
				$msg = 'testing equals()';
			}

			$this->assertTrue( $expected->equals( $actual ), $msg );
		} else {
			$this->assertEquals( $expected, $actual );
		}
	}

	/**
	 * @since 0.1
	 *
	 * @dataProvider invalidInputProvider
	 * @param mixed $value
	 * @param ValueParser|null $parser
	 */
	public function testParseWithInvalidInputs( $value, ValueParser $parser = null ) {
		if ( $parser === null ) {
			$parser = $this->getInstance();
		}

		$this->expectException( 'ValueParsers\ParseException' );
		$parser->parse( $value );
	}

	/**
	 * Returns if the result of the parsing process should be checked to be a DataValue.
	 *
	 * @since 0.1
	 *
	 * @return bool
	 */
	protected function requireDataValue() {
		return true;
	}

}
