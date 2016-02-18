<?php

namespace Wikibase\Repo\Tests\Parsers;

use PHPUnit_Framework_TestCase;
use Wikibase\Repo\Parsers\MediaWikiMonthNameProvider;

/**
 * @covers Wikibase\Repo\Parsers\MediaWikiMonthNameProvider
 *
 * @group ValueParsers
 * @group WikibaseRepo
 * @group Wikibase
 * @group TimeParsers
 *
 * @licence GNU GPL v2+
 * @author Thiemo Mättig
 */
class MediaWikiMonthNameProviderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @dataProvider monthNamesProvider
	 */
	public function testGetLocalizedMonthNames( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getLocalizedMonthNames( $languageCode );
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'string', $actual );
		$this->assertCount( 12, $actual );
	}

	public function monthNamesProvider() {
		return array(
			array( 'en' ),
			array( 'de' ),
		);
	}

	/**
	 * @dataProvider replacementsProvider
	 */
	public function testGetMonthNameUnlocalizations( $languageCode ) {
		$instance = new MediaWikiMonthNameProvider();
		$actual = $instance->getMonthNumbers( $languageCode );
		$this->assertInternalType( 'array', $actual );
		$this->assertContainsOnly( 'int', $actual );
		$this->assertGreaterThanOrEqual( 12, count( $actual ) );
	}

	public function replacementsProvider() {
		return array(
			array( 'en' ),
			array( 'de' ),
		);
	}

}
