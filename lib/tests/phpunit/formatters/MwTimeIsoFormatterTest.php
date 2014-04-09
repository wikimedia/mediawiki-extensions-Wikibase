<?php

namespace ValueFormatters\Test;

use DataValues\TimeValue;
use ValueFormatters\ValueFormatter;
use ValueFormatters\FormatterOptions;
use Wikibase\Lib\MwTimeIsoFormatter;
use Wikibase\Utils;

/**
 * @covers ValueFormatters\TimeFormatter
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 * @author Adam Shorland
 */
class MwTimeIsoFormatterTest extends \PHPUnit_Framework_TestCase {

	/**
	 * Returns an array of test parameters.
	 *
	 * @return array
	 */
	public function formatDateProvider() {
		$tests = array(
			//+ dates
			'16 August 2013' => array(
				'+2013-08-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'16 July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'14 January 1' => array(
				'+00000000001-01-14T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'1 January 10000' => array(
				'+00000010000-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'July 2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_MONTH,
			),
			'2013' => array(
				'+00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'13' => array(
				'+00000000013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'2222013' => array(
				'+00002222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'12342222013' => array(
				'+12342222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			//stepping through precisions
			'12345678910s' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'12345678920s' => array(
				'+12345678919-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'123456789. century' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'123456790. century' => array(
				'+12345678992-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'12345678. millennium' => array(
				'+12345678112-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345679. millennium' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'in 12345670000 years' => array(
				'+12345671912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12345680000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'in 12345600000 years' => array(
				'+12345618912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12345700000 years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'in 12345 million years' => array(
				'+12345178912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12346 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'in 12340 million years' => array(
				'+12341678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12350 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'in 12300 million years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'in 12400 million years' => array(
				'+12375678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'in 12 billion years' => array(
				'+12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'in 13 billion years' => array(
				'+12545678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),

			//- dates
			'16 August 2013 BCE' => array(
				'-2013-08-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'16 July 2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'14 January 1 BCE' => array(
				'-00000000001-01-14T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'1 January 10000 BCE' => array(
				'-00000010000-01-01T00:00:00Z',
				TimeValue::PRECISION_DAY,
			),
			'July 2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_MONTH,
			),
			'2013 BCE' => array(
				'-00000002013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'13 BCE' => array(
				'-00000000013-07-16T00:00:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'2222013 BCE' => array(
				'-00002222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			'12342222013 BCE' => array(
				'-12342222013-07-16T00:10:00Z',
				TimeValue::PRECISION_YEAR,
			),
			//stepping through precisions
			'12345678910s BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'12345678920s BCE' => array(
				'-12345678919-01-01T01:01:01Z',
				TimeValue::PRECISION_10a,
			),
			'123456789. century BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'123456790. century BCE' => array(
				'-12345678992-01-01T01:01:01Z',
				TimeValue::PRECISION_100a,
			),
			'12345678. millennium BCE' => array(
				'-12345678112-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345679. millennium BCE' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_ka,
			),
			'12345670000 years ago' => array(
				'-12345671912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'12345680000 years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10ka,
			),
			'12345600000 years ago' => array(
				'-12345618912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'12345700000 years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100ka,
			),
			'12345 million years ago' => array(
				'-12345178912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'12346 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ma,
			),
			'12340 million years ago' => array(
				'-12341678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'12350 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_10Ma,
			),
			'12300 million years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'12400 million years ago' => array(
				'-12375678912-01-01T01:01:01Z',
				TimeValue::PRECISION_100Ma,
			),
			'12 billion years ago' => array(
				'-12345678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'13 billion years ago' => array(
				'-12545678912-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),

			// Stuff we dont want to format so must return it :<
			'-00000000000-01-01T01:01:01Z' => array(
				'-00000000000-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'-0-01-01T01:01:01Z' => array(
				'-0-01-01T01:01:01Z',
				TimeValue::PRECISION_Ga,
			),
			'foobar' => array(
				'foobar',
				TimeValue::PRECISION_Ga,
			),
		);

		$argLists = array();

		foreach ( $tests as $expected => $args ) {
			$argLists[] = array( $expected, $args[0], $args[1], 'en' );
		}

		//Different language tests at YEAR precision
		foreach( Utils::getLanguageCodes() as $languageCode ) {
			$argLists[] = array( '3333', '+00000003333-01-01T00:00:00Z', TimeValue::PRECISION_YEAR, $languageCode );
		}

		return $argLists;
	}

	/**
	 * @dataProvider formatDateProvider
	 *
	 * @param string $expected
	 * @param string $extendedIsoString
	 * @param integer $precision
	 * @param string $langCode
	 */
	public function testFormatDate( $expected, $extendedIsoString, $precision, $langCode = 'en' ) {
		//TODO remove this skip section once $brokenLanguages can be empty! BUG 63723
		$brokenLanguages = array(
			'ab', 'als', 'ar', 'arq', 'arz', 'av', 'azb',
			'ba', 'bar', 'bcc', 'be', 'be-tarask', 'be-x-old', 'bqi', 'bxr',
			'cdo', 'ce', 'ckb', 'crh', 'crh-latn', 'crh-cyrl', 'cs', 'cu', 'cv',
			'da', 'de', 'de-at', 'de-ch', 'de-formal', 'dsb',
			'el', 'eo', 'et',
			'fa', 'fi', 'fit', 'fiu-vro', 'fo', 'frr', 'fur',
			'gan', 'gan-hans', 'gan-hant', 'gl', 'glk', 'grc', 'gsw',
			'he', 'hr', 'hrx', 'hsb', 'hu', 'hy',
			'ii', 'inh', 'is',
			'ja', 'jut',
			'kaa', 'kk', 'kk-arab', 'kk-cyrl', 'kk-latn', 'kk-cn', 'kk-kz', 'kk-tr', 'kl', 'km',
			'ko', 'ko-kp', 'koi', 'krc', 'ksh', 'ku-arab', 'kv',
			'la', 'lb', 'lbe', 'lez', 'liv', 'lzh',
			'mhr', 'mrj', 'mwl', 'myv', 'mzn',
			'nan', 'nb', 'nds', 'nn', 'no',
			'oc', 'os',
			'pdc', 'pdt', 'pfl', 'pnt', 'pt', 'pt-br',
			'ru', 'rue',
			'sah', 'sk', 'sl', 'sli', 'sr', 'sr-ec', 'sr-el', 'stq',
			'tg', 'tg-cyrl', 'th', 'tyv',
			'udm', 'uk', 'uz',
			'vep', 'vi', 'vmf', 'vo', 'vot', 'vro',
			'wuu',
			'xal',
			'yi', 'yue',
			'za', 'za', 'zh', 'zh-classical', 'zh-cn', 'zh-hans', 'zh-hant', 'zh-hk', 'zh-min-nan', 'zh-mo',
			'zh-my', 'zh-sg', 'zh-tw', 'zh-yue'
		);
		if( in_array( $langCode, $brokenLanguages ) ) {
			$this->markTestSkipped( "Test for lang {$langCode} currently broken: Bug 63723" );
		}

		$options = new FormatterOptions( array(
			ValueFormatter::OPT_LANG => $langCode
		) );

		$isoFormatter = new MwTimeIsoFormatter( $options );

		$this->assertEquals( $expected, $isoFormatter->formatDate( $extendedIsoString, $precision ) );
	}

}
