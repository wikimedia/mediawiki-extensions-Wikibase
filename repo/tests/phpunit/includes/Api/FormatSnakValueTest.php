<?php

namespace Wikibase\Repo\Tests\Api;

use ApiTestCase;
use ApiUsageException;
use DataValues\DataValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Formatters\SnakFormatter;
use Wikibase\Repo\WikibaseRepo;

/**
 * @covers \Wikibase\Repo\Api\FormatSnakValue
 *
 * @group Wikibase
 * @group WikibaseAPI
 * @group Database
 * @group medium
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class FormatSnakValueTest extends ApiTestCase {

	/** @var Item */
	protected $testingItem;
	/** @var Property */
	protected $testingProperty;

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

		return [
			[ function () {
				return [
					new StringValue( 'test' ),
					null,
					null,
					null,
					null,
					'/^test$/',
				];
			} ],
			[ function () use ( $november11 ) {
				return [
					$november11,
					null,
					null,
					null,
					null,
					'/^11 November 2013$/',
				];
			} ],
			[ function () use ( $november ) {
				return [
					$november,
					null,
					null,
					null,
					null,
					'/^November 2013$/',
				];
			} ],
			[ function () {
				return [
					new StringValue( 'http://acme.test' ),
					'string',
					SnakFormatter::FORMAT_PLAIN,
					null,
					null,
					'@^http://acme\.test$@',
				];
			} ],
			[ function () {
				return [
					new StringValue( 'http://acme.test' ),
					'string',
					SnakFormatter::FORMAT_WIKI,
					null,
					null,
					'@^http&#58;//acme\.test$@',
				];
			} ],
			[ function () {
				return [
					new StringValue( 'http://acme.test' ),
					'url',
					SnakFormatter::FORMAT_PLAIN,
					null,
					null,
					'@^http://acme\.test$@',
				];
			} ],
			[ function () {
				return [
					UnboundedQuantityValue::newFromNumber( '+12.33' ),
					'quantity',
					SnakFormatter::FORMAT_PLAIN,
					[ 'lang' => 'de' ],
					null,
					'@^12,33$@', // german decimal separator
				];
			} ],
			[ function () {
				return [
					new StringValue( 'http://acme.test' ),
					'url',
					SnakFormatter::FORMAT_WIKI,
					null,
					null,
					'@^http://acme\.test$@',
				];
			} ],
			[ function () {
				return [
					new StringValue( 'example.jpg' ),
					'commonsMedia',
					SnakFormatter::FORMAT_HTML,
					null,
					null,
					'@commons\.wikimedia\.org\/wiki\/File:Example\.jpg@',
				];
			} ],
			[ function () use ( $wordSeparator, $deletedItem ) {
				return [
					new EntityIdValue( new ItemId( 'Q404' ) ),
					'wikibase-item',
					SnakFormatter::FORMAT_HTML,
					null,
					null,
					'/^Q404' . $wordSeparator . '<span class="wb-entity-undefinedinfo">\(' .
					preg_quote( $deletedItem, '/' ) . '\)<\/span>$/',
				];
			} ],
			[ function ( self $test ) {
				$id = $test->testingItem->getId();
				$idString = $id->getSerialization();

				return [
					new EntityIdValue( $id ),
					'wikibase-item',
					SnakFormatter::FORMAT_HTML,
					null,
					null,
					'/^<a title="[^"]*' . $idString . '" href="[^"]+' . $idString .
					'">George Washington<\/a>$/',
				];
			} ],
			[ function ( self $test ) {
				$id = $test->testingItem->getId();
				$idString = $id->getSerialization();

				return [
					new EntityIdValue( $id ),
					'wikibase-item',
					SnakFormatter::FORMAT_HTML,
					[ 'lang' => 'de-ch' ], // fallback
					null,
					'/^<a title="[^"]*' . $idString . '" href="[^"]+' . $idString .
					'" lang="en">George Washington<\/a>' .
					'<sup class="wb-language-fallback-indicator">[^<>]+<\/sup>$/',
				];
			} ],
			[ function () {
				return [
					new StringValue( 'whatever' ),
					null,
					SnakFormatter::FORMAT_HTML,
					[ 'lang' => 'qqx' ],
					'P404',
					'/wikibase-snakformatter-property-not-found/',
				];
			} ],
			[ function ( self $test ) {
				$qid = $test->testingItem->getId();
				$pid = $test->testingProperty->getId();

				return [
					new EntityIdValue( $qid ),
					null,
					SnakFormatter::FORMAT_PLAIN,
					[ 'lang' => 'qqx' ],
					$pid->getSerialization(),
					'/wikibase-snakformatter-valuetype-mismatch/',
				];
			} ],
			[ function ( self $test ) {
				$pid = $test->testingProperty->getId();

				return [
					new StringValue( 'whatever' ),
					null,
					SnakFormatter::FORMAT_PLAIN,
					[ 'lang' => 'qqx' ],
					$pid->getSerialization(),
					'/^whatever$/',
				];
			} ],
			// @TODO: Add a test for identifiers, once we have these.
		];
	}

	private function saveEntities() {
		$this->testingItem = new Item();
		$this->testingItem->getFingerprint()->setLabel( 'en', 'George Washington' );

		// Set up a Property
		$this->testingProperty = new Property( null, null, 'string' );

		$store = WikibaseRepo::getEntityStore();

		// Save them, this will also automatically assign new IDs
		$store->saveEntity( $this->testingItem, 'testing', $this->getTestUser()->getUser(), EDIT_NEW );
		$store->saveEntity( $this->testingProperty, 'testing', $this->getTestUser()->getUser(), EDIT_NEW );
	}

	/**
	 * @dataProvider provideApiRequest
	 */
	public function testApiRequest( $providerCallback ) {
		$this->saveEntities();
		/**
		 * @var DataValue $value
		 * @var string|null $dataType
		 * @var string $format
		 * @var array $options
		 * @var string $propertyId
		 * @var string $pattern
		 */
		list(
			$value,
			$dataType,
			$format,
			$options,
			$propertyId,
			$pattern
			) = $providerCallback( $this );

		if ( is_callable( $value ) ) {
			$value = $value();
		}

		$params = [
			'action' => 'wbformatvalue',
			'generate' => $format,
			'datatype' => $dataType,
			'datavalue' => json_encode( $value->toArray() ),
			'property' => $propertyId,
			'options' => $options === null ? null : json_encode( $options ),
		];

		list( $resultArray, ) = $this->doApiRequest( $params );

		$this->assertIsArray( $resultArray, 'top level element must be an array' );
		$this->assertArrayHasKey( 'result', $resultArray, 'top level element must have a "result" key' );

		$this->assertMatchesRegularExpression( $pattern, $resultArray['result'] );
	}

	public function testApiRequest_cannotBeUsedTogether() {
		$params = [
			'action' => 'wbformatvalue',
			'generate' => SnakFormatter::FORMAT_HTML,
			'datatype' => 'wikibase-item',
			'datavalue' => "ignore me, I'm a dummy",
			'property' => 'bar',
			'options' => json_encode( [ 'lang' => 'qqx' ] ),
		];

		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'The parameters "datatype" and "property" can not be used together.' );
		$this->doApiRequest( $params );
	}

	public function provideInvalidParameters() {
		yield 'FORMAT_TYPE_MISMATCH' => [ [
			'action' => 'wbformatvalue',
			'generate' => SnakFormatter::FORMAT_HTML,
			'datavalue' => '{"type":"wikibase-entityid", "value": {"id":"Q10-F3"}}',
			'datatype' => 'wikibase-item',
			'options' => json_encode( [ 'lang' => 'qqx' ] ),
		] ];

		yield 'BAD_DATA_VALUE_FORMAT' => [ [
			'action' => 'wbformatvalue',
			'generate' => SnakFormatter::FORMAT_HTML,
			'datavalue' => '{"type":"wikibase-entityid", "value": {"id":"X10-F3"}}',
			'datatype' => 'wikibase-item',
			'options' => json_encode( [ 'lang' => 'qqx' ] ),
		] ];

		yield 'TYPE_UNKNOWN' => [ [
			'action' => 'wbformatvalue',
			'generate' => SnakFormatter::FORMAT_PLAIN,
			'datavalue' => '{"type":"unknown", "value": "123"}',
		] ];
	}

	/**
	 * @dataProvider provideInvalidParameters
	 */
	public function testExecute_throwsApiOnInvalidArgumentException( $params ) {
		$this->expectException( ApiUsageException::class );
		$this->expectExceptionMessage( 'An illegal set of parameters have been used.' );

		$this->doApiRequest( $params );
	}

}
