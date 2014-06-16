<?php

namespace Wikibase\Test\Api;

use DataValues\DataValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use ValueFormatters\TimeFormatter;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\SnakFormatter;

/**
 * @covers Wikibase\Api\FormatSnakValue
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group FormatSnakValueAPI
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatSnakValueTest extends \ApiTestCase {

	protected function setUp() {
		parent::setUp();

		$this->setMwGlobals( array(
			'wgArticlePath' => '/wiki/$1'
		) );
	}

	public function provideApiRequest() {
		$november11 = new TimeValue( '+2013-11-11T01:02:03Z',
			1 * 60 * 60, 0, 0,
			TimeValue::PRECISION_DAY,
			TimeFormatter::CALENDAR_GREGORIAN );

		$november = new TimeValue( '+00000002013-11-10T00:00:00Z',
			1 * 60 * 60, 0, 0,
			TimeValue::PRECISION_MONTH,
			TimeFormatter::CALENDAR_GREGORIAN );

		$wordSeparator = wfMessage( 'word-separator' )->text();
		$deletedItem = wfMessage( 'wikibase-deletedentity-item' )->inLanguage( 'en' )->text()

		return array(
			array( new StringValue( 'test' ),
				null,
				null,
				null,
				'/^test$/' ),

			array( $november11,
				null,
				null,
				array( TimeFormatter::OPT_LANG => 'en' ),
				'/^11 November 2013$/' ),

			array( $november,
				null,
				null,
				array( TimeFormatter::OPT_LANG => 'en' ),
				'/^November 2013$/' ),

			/* // TimeFormatter is currently bypassed; This test can only work once we start using it again.
			array( $november11,
				null,
				null,
				array(
					TimeFormatter::OPT_LANG => 'en',
					TimeFormatter::OPT_CALENDARNAMES => array( 'http://acme.org' => 'ACME' ),
					TimeFormatter::OPT_TIME_ISO_FORMATTER => null
				),
				'/^\+2013-11-11T01:02:03Z (ACME)$/' ),
			*/

			array( new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_PLAIN,
				null,
				'@^http://acme\.test$@' ),

			array( new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_WIKI,
				null,
				'@^http&#58;//acme\.test$@' ),

			array( new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_PLAIN,
				null,
				'@^http://acme\.test$@' ),

			array( new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_WIKI,
				null,
				'@^http://acme\.test$@' ),

			array( new StringValue( 'example.jpg' ),
				'commonsMedia',
				SnakFormatter::FORMAT_HTML,
				null,
				'@commons\.wikimedia\.org\/wiki\/File:Example\.jpg@' ),

			array( new EntityIdValue( new ItemId( 'Q45345345435' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				null,
				'/^Q45345345435' . $wordSeparator . '<span class="wb-entity-undefinedinfo">\(' . preg_quote( $deletedItem,  '/' ) . '\)<\/span>$/' ),

			// @TODO: Test an existing Item id
		);
	}

	/**
	 * @dataProvider provideApiRequest
	 */
	public function testApiRequest( DataValue $value, $dataType, $format, $options, $pattern ) {
		$params = array(
			'action' => 'wbformatvalue',
			'generate' => $format,
			'datatype' => $dataType,
			'datavalue' => json_encode( $value->toArray() ),
			'options' => $options === null ? null : json_encode( $options ),
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element must be an array' );
		$this->assertArrayHasKey( 'result', $resultArray, 'top level element must have a "result" key' );

		$this->assertRegExp( $pattern, $resultArray['result'] );
	}

}
