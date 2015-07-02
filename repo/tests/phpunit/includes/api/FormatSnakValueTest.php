<?php

namespace Wikibase\Test\Api;

use ApiTestCase;
use DataValues\DataValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Api\FormatSnakValue
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group FormatSnakValueAPI
 * @group Database
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class FormatSnakValueTest extends ApiTestCase {

	public function provideApiRequest() {
		$november11 = new TimeValue(
			'+2013-11-11T01:02:03Z',
			0, 0, 0,
			TimeValue::PRECISION_DAY,
			'http://acme.test'
		);

		$november = new TimeValue(
			'+2013-11-10T00:00:00Z',
			0, 0, 0,
			TimeValue::PRECISION_MONTH,
			'http://acme.test'
		);

		$wordSeparator = wfMessage( 'word-separator' )->text();
		$deletedItem = wfMessage( 'wikibase-deletedentity-item' )->inLanguage( 'en' )->text();

		return array(
			array(
				new StringValue( 'test' ),
				null,
				null,
				null,
				'/^test$/'
			),
			array(
				$november11,
				null,
				null,
				null,
				'/^11 November 2013$/'
			),
			array(
				$november,
				null,
				null,
				null,
				'/^November 2013$/'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_PLAIN,
				null,
				'@^http://acme\.test$@'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_WIKI,
				null,
				'@^http&#58;//acme\.test$@'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_PLAIN,
				null,
				'@^http://acme\.test$@'
			),
			array(
				QuantityValue::newFromNumber( '+12.33', '1' ),
				'quantity',
				SnakFormatter::FORMAT_PLAIN,
				array( 'lang' => 'de' ),
				'@^12,33$@' // german decimal separator
			),
			array(
				new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_WIKI,
				null,
				'@^http://acme\.test$@'
			),
			array(
				new StringValue( 'example.jpg' ),
				'commonsMedia',
				SnakFormatter::FORMAT_HTML,
				null,
				'@commons\.wikimedia\.org\/wiki\/File:Example\.jpg@'
			),
			array(
				new EntityIdValue( new ItemId( 'Q404' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				null,
				'/^Q404' . $wordSeparator . '<span class="wb-entity-undefinedinfo">\(' . preg_quote( $deletedItem, '/' ) . '\)<\/span>$/'
			),
			array(
				new EntityIdValue( new ItemId( 'Q23' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				null,
				'/^<a title="[^"]*Q23" href="[^"]+Q23">George Washington<\/a>$/'
			),
			array(
				new EntityIdValue( new ItemId( 'Q23' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				array( 'lang' => 'de-ch' ), // fallback
				'/^<a title="[^"]*Q23" href="[^"]+Q23" lang="en">George Washington<\/a><sup class="wb-language-fallback-indicator">[^<>]+<\/sup>$/'
			),

			// @TODO: Test an existing Item id
		);
	}

	private function setUpEntities() {
		global $wgUser;

		static $setup = false;

		if ( $setup ) {
			return;
		}

		$setup = true;

		$store = WikibaseRepo::getDefaultInstance()->getStore()->getEntityStore();

		// remove entities we care about
		$idsToDelete = array( new ItemId( 'Q404' ), new ItemId( 'Q23' ) );
		foreach ( $idsToDelete as $id ) {
			try {
				$store->deleteEntity( $id, 'test', $wgUser );
			} catch ( StorageException $ex ) {
				// ignore
			}
		}

		// set up Q23
		$item = new Item();
		$item->setId( new ItemId( 'Q23' ) );
		$item->getFingerprint()->setLabel( 'en', 'George Washington' );

		$store->saveEntity( $item, 'testing', $wgUser, EDIT_NEW );
	}

	/**
	 * @dataProvider provideApiRequest
	 */
	public function testApiRequest( DataValue $value, $dataType, $format, $options, $pattern ) {
		$this->setUpEntities();

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
