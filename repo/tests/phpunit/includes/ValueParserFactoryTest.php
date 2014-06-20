<?php

namespace Wikibase\Tests\Repo;

use Wikibase\Repo\ValueParserFactory;

/**
 * @covers Wikibase\Repo\ValueParserFactory
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group WikibaseRepoTest
 *
 * @licence GNU GPL v2+
 * @author Adrian Lang <adrian.lang@wikimedia.de>
 */
class ValueParserFactoryTest extends \MediaWikiTestCase {

	/**
	 * @dataProvider getParserIdsProvider
	 */
	public function testGetParserIds( $valueParsers, $expected ) {
		$valueParserFactory = new ValueParserFactory( $valueParsers );

		$returnValue = $valueParserFactory->getParserIds();

		$this->assertEquals( $expected, $returnValue );
	}

	public function getParserIdsProvider() {
		return array(
			array(
				array( 'key' => 'value' ),
				array( 'key' )
			)
		);
	}
}
