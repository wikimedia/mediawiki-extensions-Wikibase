<?php

namespace Wikibase\Repo\Tests;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
use DataValues\UnboundedQuantityValue;
use PHPUnit_Framework_TestCase;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\ItemIdParser;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\DispatchingEntityLookup;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\Lib\StaticContentLanguages;
use Wikibase\Lib\Tests\MockRepository;
use Wikibase\Repo\CachingCommonsMediaFileNameLookup;
use Wikibase\Repo\ValidatorBuilders;

/**
 * @covers Wikibase\Repo\ValidatorBuilders
 *
 * @group Wikibase
 * @group WikibaseValidators
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class ValidatorBuildersTest extends PHPUnit_Framework_TestCase {

	private function newValidatorBuilders() {
		return new ValidatorBuilders(
			$this->getEntityLookup(),
			new ItemIdParser(),
			[ 'http', 'https', 'ftp', 'mailto' ],
			'http://qudt.org/vocab/',
			new StaticContentLanguages( [ 'contentlanguage' ] ),
			$this->getCachingCommonsMediaFileNameLookup(),
			[
				'' => [ Item::ENTITY_TYPE, Property::ENTITY_TYPE ],
				'foo' => [ Item::ENTITY_TYPE ]
			]
		);
	}

	private function getEntityLookup() {
		$q8 = new Item( new ItemId( 'Q8' ) );

		$p8 = Property::newFromType( 'string' );
		$p8->setId( new PropertyId( 'P8' ) );

		$localLookup = new MockRepository();
		$localLookup->putEntity( $q8 );
		$localLookup->putEntity( $p8 );

		$fooQ123 = new ItemId( 'foo:Q123' );
		$fooP42 = new PropertyId( 'foo:P42' );

		$foreignLookup = $this->getMock( EntityLookup::class );
		$foreignLookup->method( 'hasEntity' )
			->willReturnCallback( function( EntityId $id ) use ( $fooQ123, $fooP42 ) {
				return $id->equals( $fooQ123 ) || $id->equals( $fooP42 );
			} );

		return new DispatchingEntityLookup( [
			'' => $localLookup,
			'foo' => $foreignLookup,
		] );
	}

	/**
	 * @return CachingCommonsMediaFileNameLookup
	 */
	private function getCachingCommonsMediaFileNameLookup() {
		$fileNameLookup = $this->getMockBuilder( CachingCommonsMediaFileNameLookup::class )
			->disableOriginalConstructor()
			->getMock();

		$fileNameLookup->expects( $this->any() )
			->method( 'lookupFileName' )
			->with( $this->isType( 'string' ) )
			->will( $this->returnCallback( function( $fileName ) {
				return strpos( $fileName, 'NOT-FOUND' ) === false ? $fileName : null;
			} ) );

		return $fileNameLookup;
	}

	public function provideStringValueValidation() {
		return [
			'Space' => [ 'x x', true ],
			'Unicode support' => [ 'Äöü', true ],

			// Length checks
			'To short' => [ '', false ],
			'Minimum length' => [ 'x', true ],
			'Maximum length' => [ str_repeat( 'x', 400 ), true ],
			'Too long' => [ str_repeat( 'x', 401 ), false ],

			// Enforced trimming
			'Leading space' => [ ' x', false ],
			'Leading newline' => [ "\nx", false ],
			'Trailing space' => [ 'x ', false ],
			'Trailing newline' => [ "x\n", false ],

			// Disallowed whitespace characters
			'U+0009: Tabulator' => [ "x\tx", false ],
			'U+000A: Newline' => [ "x\nx", false ],
			'U+000B: Vertical tab' => [ "x\x0Bx", false ],
			'U+000C: Form feed' => [ "x\fx", false ],
			'U+000D: Return' => [ "x\rx", false ],
			'U+0085: Next line' => [ "x\x85x", false ],
		];
	}

	/**
	 * @dataProvider provideStringValueValidation
	 */
	public function testStringValueValidation( $string, $expected ) {
		$value = new StringValue( $string );
		$validators = $this->newValidatorBuilders()->buildStringValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideDataTypeValidation() {
		$latLonValue = new LatLongValue( 0, 0 );
		$wikidataUri = 'http://www.wikidata.org/entity/';

		$cases = array(
			//wikibase-item
			'Expected item, string supplied' => [ 'wikibase-item', 'q8', false ],
			'Expected item, StringValue supplied' => [ 'wikibase-item', new StringValue( 'q8' ), false ],
			'Existing item' => [ 'wikibase-item', new EntityIdValue( new ItemId( 'q8' ) ), true ],
			'Missing item' => [ 'wikibase-item', new EntityIdValue( new ItemId( 'q3' ) ), false ],
			'Not an item' => [ 'wikibase-item', new EntityIdValue( new PropertyId( 'p8' ) ), false ],

			// wikibase-property
			'Existing entity' => [ 'wikibase-property', new EntityIdValue( new PropertyId( 'p8' ) ), true ],
			'Not a property' => [ 'wikibase-property', new EntityIdValue( new ItemId( 'q8' ) ), false ],

			// generic wikibase entity
			'Existing property' => [ 'wikibase-entity', new EntityIdValue( new PropertyId( 'p8' ) ), true ],
			'Existing item entity' => [ 'wikibase-entity', new EntityIdValue( new ItemId( 'q8' ) ), true ],
			'Missing item entity' => [ 'wikibase-entity', new EntityIdValue( new ItemId( 'q3' ) ), false ],
			'Unknown repository' => [ 'wikibase-entity', new EntityIdValue( new ItemId( 'bar:Q123' ) ), false ],
			'Foreign entity' => [ 'wikibase-entity', new EntityIdValue( new ItemId( 'foo:Q123' ) ), true ],
			'Unsupported foreign entity type' => [ 'wikibase-entity', new EntityIdValue( new PropertyId( 'foo:P42' ) ), false ],
			'Expected EntityId, StringValue supplied' => [ 'wikibase-entity', new StringValue( 'q8' ), false ],

			//commonsMedia
			'Commons expects StringValue, got string' => [ 'commonsMedia', 'Foo.jpg', false ],
			'Commons expects StringValue' => [ 'commonsMedia', new NumberValue( 7 ), false ],
			'Commons can not be empty' => [ 'commonsMedia', new StringValue( '' ), false ],
			'Name too long' => [ 'commonsMedia', new StringValue( str_repeat( 'x', 237 ) . '.jpg' ), false ],
			'No file extension' => [ 'commonsMedia', new StringValue( 'Foo' ), false ],
			'File extension to short' => [ 'commonsMedia', new StringValue( 'Foo.a' ), false ],
			'This should be good' => [ 'commonsMedia', new StringValue( 'Foo.jpg' ), true ],
			'Illegal character: newline' => [ 'commonsMedia', new StringValue( "a\na.jpg" ), false ],
			'Illegal character: open square bracket' => [ 'commonsMedia', new StringValue( 'a[a.jpg' ), false ],
			'Illegal character: close square bracket' => [ 'commonsMedia', new StringValue( 'a]a.jpg' ), false ],
			'Illegal character: open curly bracket' => [ 'commonsMedia', new StringValue( 'a{a.jpg' ), false ],
			'Illegal character: close curly bracket' => [ 'commonsMedia', new StringValue( 'a}a.jpg' ), false ],
			'Illegal character: pipe' => [ 'commonsMedia', new StringValue( 'a|a.jpg' ), false ],
			'Illegal character: hash' => [ 'commonsMedia', new StringValue( 'Foo#bar.jpg' ), false ],
			'Illegal character: colon' => [ 'commonsMedia', new StringValue( 'Foo:bar.jpg' ), false ],
			'Illegal character: slash' => [ 'commonsMedia', new StringValue( 'Foo/bar.jpg' ), false ],
			'Illegal character: backslash' => [ 'commonsMedia', new StringValue( 'Foo\bar.jpg' ), false ],
			'Commons Unicode support' => [ 'commonsMedia', new StringValue( 'Äöü.jpg' ), true ],
			'Media name with leading space' => [ 'commonsMedia', new StringValue( ' Foo.jpg' ), false ],
			'Media name with trailing space' => [ 'commonsMedia', new StringValue( 'Foo.jpg ' ), false ],
			'File not found' => [ 'commonsMedia', new StringValue( 'Foo-NOT-FOUND.jpg' ), false ],

			//string
			'String expects StringValue, got string' => [ 'string', 'Foo', false ],
			'String expects StringValue' => [ 'string', new NumberValue( 7 ), false ],

			//time
			'TimeValue expected, string supplied' => [ 'time', 'Foo', false ],
			'TimeValue expected' => [ 'time', new NumberValue( 7 ), false ],

			//time['calendar-model']
			'Calendar: too short' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, '1' ),
				false
			],
			'Calendar: too long' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false
			],
			'Calendar: URL' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				true
			],
			'Calendar: untrimmed' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' ' . $wikidataUri . 'Q1985727 ' ),
				false
			],
			'Calendar: bad URL' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' javascript:alert(1)' ),
				false
			],

			//precision to the second (currently not allowed)
			'Time given to the second' => [
				'time',
				new TimeValue( '+2013-06-06T11:22:33Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				false
			],
			'Precision: second' => [
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND,
					$wikidataUri . 'Q1985727' ),
				false
			],

			//TODO: calendar must be an item reference
			//TODO: calendar must be from a list of configured values

			//globe-coordinate
			'GlobeCoordinateValue expected, string supplied' => [ 'globe-coordinate', 'Foo', false ],
			'GlobeCoordinateValue expected' => [ 'globe-coordinate', new NumberValue( 7 ), false ],

			//globe-coordinate[precision]
			'Integer precision is valid' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true
			],
			'Float precision is valid' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 0.2, $wikidataUri . 'Q2' ),
				true
			],
			'Null precision is invalid' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, null, $wikidataUri . 'Q2' ),
				false
			],

			//globe-coordinate[globe]
			// FIXME: this is testing unimplemented behaviour? Probably broken...
			'Globe can not be empty' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, '' ),
				false
			],
			'Globe: too long' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false
			],
			'Globe: URL' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true
			],
			'Globe: untrimmed' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' ' . $wikidataUri . 'Q2 ' ),
				false
			],
			'Globe: bad URL scheme' => [
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' javascript:alert(1) ' ),
				false
			],
			//TODO: globe must be an item reference
			//TODO: globe must be from a list of configured values

			// url
			'URL expects StringValue, got string' => [ 'url', 'Foo', false ],
			'URL expects StringValue' => [ 'url', new NumberValue( 7 ), false ],

			'Simple HTTP URL' => [ 'url', new StringValue( 'http://acme.com' ), true ],
			'Simple HTTPS URL' => [ 'url', new StringValue( 'https://acme.com' ), true ],
			'Simple FTP URL' => [ 'url', new StringValue( 'ftp://acme.com' ), true ],
			'Complex HTTP URL' => [ 'url', new StringValue( 'http://acme.com/foo/bar?some=stuff#fragment' ), true ],

			// evil url
			'Protocol-relative' => [ 'url', new StringValue( '//bla' ), false ],
			'Relative path' => [ 'url', new StringValue( '/bla/bla' ), false ],
			'Just words' => [ 'url', new StringValue( 'just stuff' ), false ],
			'JavaScript URL' => [ 'url', new StringValue( 'javascript:alert("evil")' ), false ],
			'Bad http URL' => [ 'url', new StringValue( 'http://' ), false ],
			'URL too long' => [ 'url', new StringValue( 'http://' . str_repeat( 'x', 494 ) ), false ],

			'URL with leading space' => [ 'url', new StringValue( ' http://acme.com' ), false ],
			'URL with trailing space' => [ 'url', new StringValue( 'http://acme.com ' ), false ],

			//quantity
			'Unbounded' => [ 'quantity', UnboundedQuantityValue::newFromNumber( 5 ), true ],
			'Simple integer' => [ 'quantity', QuantityValue::newFromNumber( 5 ), true ],
			'Vocabulary URI' => [ 'quantity', QuantityValue::newFromNumber( 5, 'http://qudt.org/vocab/unit#Meter' ), true ],
			'Wikidata URI' => [ 'quantity', QuantityValue::newFromNumber( 5, $wikidataUri . 'Q11573' ), false ],
			'1 means unitless' => [ 'quantity', QuantityValue::newFromNumber( 5, '1' ), true ],
			'Bad unit URI' => [ 'quantity', QuantityValue::newFromNumber( 5, 'kittens' ), false ],
			'Decimal strings' => [ 'quantity', QuantityValue::newFromNumber( '-11.234', '1', '-10', '-12' ), true ],

			//monolingual text
			'Simple value' => [ 'monolingualtext', new MonolingualTextValue( 'contentlanguage', 'text' ), true ],
			'Not a valid language' => [ 'monolingualtext', new MonolingualTextValue( 'en', 'text' ), false ],
		);

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypeValidation
	 */
	public function testDataTypeValidation( $typeId, $value, $expected ) {
		$builders = $this->newValidatorBuilders();

		$validatorMap = array(
			'commonsMedia'      => array( $builders, 'buildMediaValidators' ),
			'globe-coordinate'  => array( $builders, 'buildCoordinateValidators' ),
			'monolingualtext'   => array( $builders, 'buildMonolingualTextValidators' ),
			'quantity'          => array( $builders, 'buildQuantityValidators' ),
			'string'            => array( $builders, 'buildStringValidators' ),
			'time'              => array( $builders, 'buildTimeValidators' ),
			'url'               => array( $builders, 'buildUrlValidators' ),
			'wikibase-entity'   => array( $builders, 'buildEntityValidators' ),
			'wikibase-item'     => array( $builders, 'buildItemValidators' ),
			'wikibase-property' => array( $builders, 'buildPropertyValidators' ),
		);

		$validators = call_user_func( $validatorMap[$typeId] );

		$this->assertValidation( $expected, $validators, $value );
	}

	/**
	 * @param bool $expected
	 * @param ValueValidator[] $validators
	 * @param mixed $value
	 */
	protected function assertValidation( $expected, array $validators, $value ) {
		$result = Result::newSuccess();
		foreach ( $validators as $validator ) {
			$result = $validator->validate( $value );

			if ( !$result->isValid() ) {
				break;
			}
		}

		if ( $expected ) {
			$errors = $result->getErrors();
			if ( !empty( $errors ) ) {
				$this->fail( $errors[0]->getText() );
			}

			$this->assertEquals( $expected, $result->isValid() );
		} else {
			$this->assertEquals( $expected, $result->isValid() );
		}
	}

}
