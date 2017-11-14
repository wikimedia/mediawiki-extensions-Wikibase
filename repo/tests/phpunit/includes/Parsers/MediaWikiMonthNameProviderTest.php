<?php

namespace Wikibase\Repo\Tests\Parsers;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Parsers\MediaWikiMonthNameProvider;

/**
 * @covers Wikibase\Repo\Parsers\MediaWikiMonthNameProvider
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0+
 * @author Thiemo Kreuz
 */
class MediaWikiMonthNameProviderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetLocalizedMonthNames( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getLocalizedMonthNames( $languageCode );
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertCount( 12, $actual );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetMonthNumbers( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getMonthNumbers( $languageCode );
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'int', $actual );
		$this->assertGreaterThanOrEqual( 12, count( $actual ) );
	}

	public function languageCodeProvider() {
		return [
			[ 'en' ],
			[ 'de' ],
		];
	}

}
