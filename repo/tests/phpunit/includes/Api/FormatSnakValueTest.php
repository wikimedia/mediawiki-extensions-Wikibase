<?php

namespace Wikibase\Test\Repo\Api;

use ApiTestCase;
use DataValues\DataValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use UsageException;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers Wikibase\Repo\Api\FormatSnakValue
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group FormatSnakValueAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0+
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
				null,
				'/^test$/'
			),
			array(
				$november11,
				null,
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
				null,
				'/^November 2013$/'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_PLAIN,
				null,
				null,
				'@^http://acme\.test$@'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'string',
				SnakFormatter::FORMAT_WIKI,
				null,
				null,
				'@^http&#58;//acme\.test$@'
			),
			array(
				new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_PLAIN,
				null,
				null,
				'@^http://acme\.test$@'
			),
			array(
				QuantityValue::newFromNumber( '+12.33', '1' ),
				'quantity',
				SnakFormatter::FORMAT_PLAIN,
				array( 'lang' => 'de' ),
				null,
				'@^12,33$@' // german decimal separator
			),
			array(
				new StringValue( 'http://acme.test' ),
				'url',
				SnakFormatter::FORMAT_WIKI,
				null,
				null,
				'@^http://acme\.test$@'
			),
			array(
				new StringValue( 'example.jpg' ),
				'commonsMedia',
				SnakFormatter::FORMAT_HTML,
				null,
				null,
				'@commons\.wikimedia\.org\/wiki\/File:Example\.jpg@'
			),
			array(
				new EntityIdValue( new ItemId( 'Q404' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				null,
				null,
				'/^Q404' . $wordSeparator . '<span class="wb-entity-undefinedinfo">\('
					. preg_quote( $deletedItem, '/' ) . '\)<\/span>$/'
			),
			array(
				new EntityIdValue( new ItemId( 'Q23' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				null,
				null,
				'/^<a title="[^"]*Q23" href="[^"]+Q23">George Washington<\/a>$/'
			),
			array(
				new EntityIdValue( new ItemId( 'Q23' ) ),
				'wikibase-item',
				SnakFormatter::FORMAT_HTML,
				array( 'lang' => 'de-ch' ), // fallback
				null,
				'/^<a title="[^"]*Q23" href="[^"]+Q23" lang="en">George Washington<\/a>'
					. '<sup class="wb-language-fallback-indicator">[^<>]+<\/sup>$/'
			),
			array(
				new StringValue( 'whatever' ),
				null,
				SnakFormatter::FORMAT_HTML,
				array( 'lang' => 'qqx' ),
				'P404',
				'/wikibase-snakformatter-property-not-found/'
			),
			array(
				new EntityIdValue( new ItemId( 'Q23' ) ),
				null,
				SnakFormatter::FORMAT_PLAIN,
				array( 'lang' => 'qqx' ),
				'P42',
				'/wikibase-snakformatter-valuetype-mismatch/'
			),
			array(
				new StringValue( 'whatever' ),
				null,
				SnakFormatter::FORMAT_PLAIN,
				array( 'lang' => 'qqx' ),
				'P42',
				'/^whatever$/'
			),
			// @TODO: Add a test for identifiers, once we have these.
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
		$idsToDelete = array( new ItemId( 'Q404' ), new ItemId( 'Q23' ), new PropertyId( 'P404' ) );
		foreach ( $idsToDelete as $id ) {
			try {
				$store->deleteEntity( $id, 'test', $wgUser );
			} catch ( StorageException $ex ) {
				// ignore
			}
		}

		// Set up Q23
		$item = new Item( new ItemId( 'Q23' ) );
		$item->getFingerprint()->setLabel( 'en', 'George Washington' );

		// Set up P42
		$property = new Property( new PropertyId( 'P42' ), null, 'string' );

		$store->saveEntity( $item, 'testing', $wgUser, EDIT_NEW );
		$store->saveEntity( $property, 'testing', $wgUser, EDIT_NEW );
	}

	/**
	 * @dataProvider provideApiRequest
	 */
	public function testApiRequest(
		DataValue $value,
		$dataType,
		$format,
		$options,
		$propertyId,
		$pattern
	) {
		$this->setUpEntities();

		$params = array(
			'action' => 'wbformatvalue',
			'generate' => $format,
			'datatype' => $dataType,
			'datavalue' => json_encode( $value->toArray() ),
			'property' => $propertyId,
			'options' => $options === null ? null : json_encode( $options ),
		);

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertInternalType( 'array', $resultArray, 'top level element must be an array' );
		$this->assertArrayHasKey( 'result', $resultArray, 'top level element must have a "result" key' );

		$this->assertRegExp( $pattern, $resultArray['result'] );
	}

	public function testApiRequest_cannotBeUsedTogether() {
		$params = array(
			'action' => 'wbformatvalue',
			'generate' => SnakFormatter::FORMAT_HTML,
			'datatype' => 'wikibase-item',
			'datavalue' => "ignore me, I'm a dummy",
			'property' => 'bar',
			'options' => json_encode( array( 'lang' => 'qqx' ) ),
		);

		$this->setExpectedException(
			UsageException::class,
			'The parameters datatype, property can not be used together'
		);
		$this->doApiRequest( $params );
	}

}
