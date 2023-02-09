<?php

namespace Wikibase\Repo\Tests\Parsers;

use DataValues\TimeValue;
use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use PHPUnit\Framework\TestCase;
use ValueParsers\ParseException;
use ValueParsers\ParserOptions;
use ValueParsers\ValueParser;
use Wikibase\Repo\Parsers\DateFormatParser;
use Wikibase\Repo\Parsers\MwDateFormatParserFactory;

/**
 * @covers \Wikibase\Repo\Parsers\MwDateFormatParserFactory
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MwDateFormatParserFactoryTest extends TestCase {

	/**
	 * @var MwDateFormatParserFactory
	 */
	private $factory;

	protected function setUp(): void {
		$this->factory = new MwDateFormatParserFactory();
	}

	public function testGetMwDateFormatParser() {
		$parser = $this->factory->getMwDateFormatParser();
		$this->assertInstanceOf( ValueParser::class, $parser );
	}

	public function testGetMwDateFormatParserWithParameters() {
		$parser = $this->factory->getMwDateFormatParser( 'en', 'dmy', 'date' );
		$this->assertInstanceOf( ValueParser::class, $parser );
	}

	public function testGetMwDateFormatParserWithInvalidLanguageCode() {
		$this->expectException( InvalidArgumentException::class );
		$this->factory->getMwDateFormatParser( null );
	}

	public function testGetMwDateFormatParserWithInvalidFormat() {
		$this->expectException( InvalidArgumentException::class );
		$this->factory->getMwDateFormatParser( 'en', null );
	}

	public function testGetMwDateFormatParserWithInvalidFormatType() {
		$this->expectException( InvalidArgumentException::class );
		$this->factory->getMwDateFormatParser( 'en', 'dmy', null );
	}

	/**
	 * @dataProvider validInputProvider
	 */
	public function testParseWithValidInputs(
		$input,
		TimeValue $expected,
		$languageCode,
		$dateFormatPreference,
		$dateFormatType
	) {
		$parser = $this->factory->getMwDateFormatParser(
			$languageCode,
			$dateFormatPreference,
			$dateFormatType
		);
		$parsed = $parser->parse( $input );
		$this->assertTrue( $expected->equals( $parsed ), $input . ' became ' . $parsed );
	}

	public function validInputProvider() {
		$dateFormatPreferences = [
			'mdy' => TimeValue::PRECISION_MINUTE,
			'dmy' => TimeValue::PRECISION_MINUTE,
			'ymd' => TimeValue::PRECISION_MINUTE,
			'ISO 8601' => TimeValue::PRECISION_SECOND,
		];
		$dateFormatTypes = [
			'date' => TimeValue::PRECISION_DAY,
			'monthonly' => TimeValue::PRECISION_MONTH,
			'both' => null,
		];

		$languageFactory = MediaWikiServices::getInstance()->getLanguageFactory();
		foreach ( $this->getLanguageCodes() as $languageCode ) {
			$language = $languageFactory->getLanguage( $languageCode );

			foreach ( $dateFormatPreferences as $dateFormatPreference => $maximumPrecision ) {
				foreach ( $dateFormatTypes as $dateFormatType => $precision ) {
					$mwTimestamp = $this->generateMwTimestamp();

					$dateFormat = $language->getDateFormatString( $dateFormatType, $dateFormatPreference );
					$input = $language->sprintfDate( $dateFormat, $mwTimestamp );

					if ( $precision === null ) {
						$precision = $maximumPrecision;
					}
					$calendarModel = intval( substr( $mwTimestamp, 0, 4 ) ) <= 1582
						? TimeValue::CALENDAR_JULIAN
						: TimeValue::CALENDAR_GREGORIAN;
					$expected = new TimeValue(
						$this->getIsoTimestamp( $mwTimestamp, $precision ),
						0, 0, 0,
						$precision,
						$calendarModel
					);

					yield [
						$input,
						$expected,
						$languageCode,
						$dateFormatPreference,
						$dateFormatType,
					];
				}
			}
		}
	}

	private function getLanguageCodes() {
		// Focus on a critical subset of languages. Enable the following MediaWiki dependency to
		// test the full set of all 400+ supported languages. This may take several minutes.
		// return array_keys( MediaWikiServices::getInstance()->getLanguageNameUtils()->getLanguageNames() );
		return [
			'ace',
			'anp',
			'bo',
			'de',
			'en',
			'fa', // right-to-left
			'gan',
			'haw',
			'krj',
			'ln',
			'lzh', // Chinese
			'lzz',
			'nn',
			'pt',
			'sma',
			'sv',
			'ty',
			'udm',
			'vi',
			'zh-hans', // Chinese
			'zh-hant', // Chinese
		];
	}

	/**
	 * @return string
	 */
	private function generateMwTimestamp() {
		static $mwTimestamps;

		if ( $mwTimestamps === null ) {
			$mwTimestamps = [
				'12010304054201',
				'19701110064300',
				'20301231234500',
			];

			for ( $i = 1; $i <= 12; $i++ ) {
				$mwTimestamps[] = sprintf( '2014%02d01224400', $i );
			}
		}

		$mwTimestamp = next( $mwTimestamps );

		if ( !$mwTimestamp ) {
			$mwTimestamp = reset( $mwTimestamps );
		}

		return $mwTimestamp;
	}

	/**
	 * @param string $mwTimestamp
	 * @param int $precision
	 *
	 * @return string
	 */
	private function getIsoTimestamp( $mwTimestamp, $precision ) {
		if ( $precision <= TimeValue::PRECISION_YEAR ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 4 ) . '0000000000';
		} elseif ( $precision === TimeValue::PRECISION_MONTH ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 6 ) . '00000000';
		} elseif ( $precision === TimeValue::PRECISION_DAY ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 8 ) . '000000';
		} elseif ( $precision === TimeValue::PRECISION_HOUR ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 10 ) . '0000';
		} elseif ( $precision === TimeValue::PRECISION_MINUTE ) {
			$mwTimestamp = substr( $mwTimestamp, 0, 12 ) . '00';
		}

		return preg_replace( '/(....)(..)(..)(..)(..)(..)/s', '+$1-$2-$3T$4:$5:$6Z', $mwTimestamp );
	}

	/**
	 * @dataProvider invalidInputProvider
	 */
	public function testParseWithInvalidInputs( $input ) {
		$parser = $this->factory->getMwDateFormatParser();
		$this->expectException( ParseException::class );
		$parser->parse( $input );
	}

	public function invalidInputProvider() {
		return [
			[ null ],
			[ true ],
			[ 2015 ],
			[ '2015' ],
		];
	}

	/**
	 * This test is supposed to break the moment Language::sprintfDate starts supporting one of the
	 * currently unused character sequences listed in the provider. When this happens, remove the
	 * sequence here and add it to the DateFormatParser base class.
	 *
	 * @dataProvider nonSpecialFormatStringProvider
	 */
	public function testNonSpecialFormatStringFeatureParity( $format, $expected ) {
		$en = MediaWikiServices::getInstance()->getLanguageFactory()->getLanguage( 'en' );
		$formatted = $en->sprintfDate( $format, '20140901123040' );
		$this->assertSame( $expected, $formatted );
	}

	/**
	 * This test is supposed to break the moment the DateFormatParser base class starts supporting
	 * one of the character sequences listed in the provider. When this happens, make sure this is
	 * intended and in parity with Language::sprintfDate and remove the sequence here.
	 *
	 * @dataProvider nonSpecialFormatStringProvider
	 */
	public function testNonSpecialFormatStringRoundtrip( $format, $expected ) {
		$parser = new DateFormatParser( new ParserOptions( [
			DateFormatParser::OPT_DATE_FORMAT => 'Y ' . $format,
		] ) );
		$timeValue = $parser->parse( '2014 ' . $expected );
		$this->assertInstanceOf( TimeValue::class, $timeValue );
	}

	public function nonSpecialFormatStringProvider() {
		return [
			[
				'B C E J K Q R S V X',
				'B C E J K Q R S V X',
			],
			[
				'b f k p q u v',
				'b f k p q u v',
			],
			[
				'XB XC XE XJ XK XQ XR XS XV XX',
				'XB XC XE XJ XK XQ XR XS XV XX',
			],
			[
				'xa xb xc xd xe xf xp xl xq xs xu xv xw xy xz',
				'a b c d e f p l q s u v w y z',
			],
			[
				'xiA xiB xiC xiD xiE xiG xiH xiI xiJ xiK xiL xiM xiN xiO xiQ xiR xiS xiT xiU xiV xiW xiX',
				'A B C D E G H I J K L M N O Q R S T U V W X',
			],
			[
				'xia xib xic xid xie xif xig xih xii xik xil xim xio xip xiq xir xis xiu xiv xiw xix',
				'a b c d e f g h i k l m o p q r s u v w x',
			],
			[
				'xjA xjB xjC xjD xjE xjG xjH xjI xjJ xjK xjL xjM xjN xjO xjP xjQ xjR xjS xjT xjU xjV xjW xjX',
				'A B C D E G H I J K L M N O P Q R S T U V W X',
			],
			[
				'xja xjb xjc xjd xje xjf xjg xjh xji xjk xjl xjm xjo xjp xjq xjr xjs xju xjv xjw xjy xjz',
				'a b c d e f g h i k l m o p q r s u v w y z',
			],
			[
				'xkA xkB xkC xkD xkE xkF xkG xkH xkI xkJ xkK xkL xkM xkN xkO xkP xkQ xkR xkS xkT xkU xkV xkW xkX xkZ',
				'A B C D E F G H I J K L M N O P Q R S T U V W X Z',
			],
			[
				'xka xkb xkc xkd xke xkf xkg xkh xki xkj xkk xkl xkm xkn xko xkp xkq xkr xks xkt xku xkv xkw xkx xky xkz',
				'a b c d e f g h i j k l m n o p q r s t u v w x y z',
			],
			[
				'xmA xmB xmC xmD xmE xmG xmH xmI xmJ xmK xmL xmM xmN xmO xmP xmQ xmR xmS xmT xmU xmV xmW xmX xmZ',
				'A B C D E G H I J K L M N O P Q R S T U V W X Z',
			],
			[
				'xma xmb xmc xmd xme xmf xmg xmh xmi xmk xml xmm xmo xmp xmq xmr xms xmt xmu xmv xmw xmx xmy xmz',
				'a b c d e f g h i k l m o p q r s t u v w x y z',
			],
		];
	}

}
