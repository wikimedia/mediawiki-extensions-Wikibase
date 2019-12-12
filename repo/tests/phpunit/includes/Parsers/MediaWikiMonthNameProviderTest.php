<?php

namespace Wikibase\Repo\Tests\Parsers;

use Wikibase\Repo\Parsers\MediaWikiMonthNameProvider;

/**
 * @covers \Wikibase\Repo\Parsers\MediaWikiMonthNameProvider
 *
 * @group ValueParsers
 * @group Wikibase
 * @group TimeParsers
 *
 * @license GPL-2.0-or-later
 * @author Thiemo Kreuz
 */
class MediaWikiMonthNameProviderTest extends \PHPUnit\Framework\TestCase {

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetLocalizedMonthNames( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getLocalizedMonthNames( $languageCode );
		$this->assertIsArray( $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertCount( 12, $actual );
	}

	/**
	 * @dataProvider languageCodeProvider
	 */
	public function testGetMonthNumbers( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getMonthNumbers( $languageCode );
		$this->assertIsArray( $actual );
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
