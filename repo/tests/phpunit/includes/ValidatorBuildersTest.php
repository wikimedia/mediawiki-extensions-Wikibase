<?php

namespace Wikibase\Test;

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
use Wikibase\DataModel\Entity\EntityIdValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Entity\BasicEntityIdParser;
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
		$entityIdParser = new BasicEntityIdParser();

		$q8 = new Item( new ItemId( 'Q8' ) );

		$p8 = Property::newFromType( 'string' );
		$p8->setId( new PropertyId( 'P8' ) );

		$entityLookup = new MockRepository();
		$entityLookup->putEntity( $q8 );
		$entityLookup->putEntity( $p8 );

		$urlSchemes = [ 'http', 'https', 'ftp', 'mailto' ];

		$builders = new ValidatorBuilders(
			$entityLookup,
			$entityIdParser,
			$urlSchemes,
			'http://qudt.org/vocab/',
			new StaticContentLanguages( [ 'contentlanguage' ] ),
			$this->getCachingCommonsMediaFileNameLookup()
		);

		return $builders;
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

	public function provideDataTypeValidation() {
		$latLonValue = new LatLongValue( 0, 0 );
		$wikidataUri = 'http://www.wikidata.org/entity/';

		$cases = [
			//wikibase-item
			[ 'wikibase-item', 'q8', false, 'Expected EntityId, string supplied' ],
			[ 'wikibase-item', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ],
			[ 'wikibase-item', new EntityIdValue( new ItemId( 'q8' ) ), true, 'existing entity' ],
			[ 'wikibase-item', new EntityIdValue( new ItemId( 'q3' ) ), false, 'missing entity' ],
			[ 'wikibase-item', new EntityIdValue( new PropertyId( 'p8' ) ), false, 'not an item' ],

			// wikibase-property
			[ 'wikibase-property', new EntityIdValue( new PropertyId( 'p8' ) ), true, 'existing entity' ],
			[ 'wikibase-property', new EntityIdValue( new ItemId( 'q8' ) ), false, 'not a property' ],

			// generic wikibase entity
			[ 'wikibase-entity', new EntityIdValue( new PropertyId( 'p8' ) ), true, 'existing entity' ],
			[ 'wikibase-entity', new EntityIdValue( new ItemId( 'q8' ) ), true, 'existing entity' ],
			[ 'wikibase-entity', new EntityIdValue( new ItemId( 'q3' ) ), false, 'missing entity' ],
			[ 'wikibase-entity', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ],

			//commonsMedia
			[ 'commonsMedia', 'Foo.jpg', false, 'StringValue expected, string supplied' ],
			[ 'commonsMedia', new NumberValue( 7 ), false, 'StringValue expected' ],
			[ 'commonsMedia', new StringValue( '' ), false, 'empty string should be invalid' ],
			[ 'commonsMedia', new StringValue( str_repeat( 'x', 237 ) . '.jpg' ), false, 'name too long' ],
			[ 'commonsMedia', new StringValue( 'Foo' ), false, 'no file extension' ],
			[ 'commonsMedia', new StringValue( 'Foo.a' ), false, 'file extension to short' ],
			[ 'commonsMedia', new StringValue( 'Foo.jpg' ), true, 'this should be good' ],
			[ 'commonsMedia', new StringValue( "a\na.jpg" ), false, 'illegal character: newline' ],
			[ 'commonsMedia', new StringValue( 'a[a.jpg' ), false, 'illegal character: square bracket' ],
			[ 'commonsMedia', new StringValue( 'a]a.jpg' ), false, 'illegal character: square bracket' ],
			[ 'commonsMedia', new StringValue( 'a{a.jpg' ), false, 'illegal character: curly bracket' ],
			[ 'commonsMedia', new StringValue( 'a}a.jpg' ), false, 'illegal character: curly bracket' ],
			[ 'commonsMedia', new StringValue( 'a|a.jpg' ), false, 'illegal character: pipe' ],
			[ 'commonsMedia', new StringValue( 'Foo#bar.jpg' ), false, 'illegal character: hash' ],
			[ 'commonsMedia', new StringValue( 'Foo:bar.jpg' ), false, 'illegal character: colon' ],
			[ 'commonsMedia', new StringValue( 'Foo/bar.jpg' ), false, 'illegal character: slash' ],
			[ 'commonsMedia', new StringValue( 'Foo\bar.jpg' ), false, 'illegal character: backslash' ],
			[ 'commonsMedia', new StringValue( 'Äöü.jpg' ), true, 'Unicode support' ],
			[ 'commonsMedia', new StringValue( ' Foo.jpg' ), false, 'media name with leading space' ],
			[ 'commonsMedia', new StringValue( 'Foo.jpg ' ), false, 'media name with trailing space' ],
			[ 'commonsMedia', new StringValue( 'Foo-NOT-FOUND.jpg' ), false, 'file not found' ],

			//string
			[ 'string', 'Foo', false, 'StringValue expected, string supplied' ],
			[ 'string', new NumberValue( 7 ), false, 'StringValue expected' ],
			[ 'string', new StringValue( '' ), false, 'empty string should be invalid' ],
			[ 'string', new StringValue( 'Foo' ), true, 'simple string' ],
			[ 'string', new StringValue( 'Äöü' ), true, 'Unicode support' ],
			[ 'string', new StringValue( str_repeat( 'x', 400 ) ), true, 'long, but not too long' ],
			[ 'string', new StringValue( str_repeat( 'x', 401 ) ), false, 'too long' ],
			[ 'string', new StringValue( ' Foo' ), false, 'string with leading space' ],
			[ 'string', new StringValue( 'Foo ' ), false, 'string with trailing space' ],

			//time
			[ 'time', 'Foo', false, 'TimeValue expected, string supplied' ],
			[ 'time', new NumberValue( 7 ), false, 'TimeValue expected' ],

			//time['calendar-model']
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, '1' ),
				false,
				'calendar: too short'
			],
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false,
				'calendar: too long'
			],
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				true,
				'calendar: URL'
			],
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' ' . $wikidataUri . 'Q1985727 ' ),
				false,
				'calendar: untrimmed'
			],
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' javascript:alert(1)' ),
				false,
				'calendar: bad URL'
			],

			//precision to the second (currently not allowed)
			[
				'time',
				new TimeValue( '+2013-06-06T11:22:33Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				false,
				'time given to the second'
			],
			[
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND,
					$wikidataUri . 'Q1985727' ),
				false,
				'precision: second'
			],

			//TODO: calendar must be an item reference
			//TODO: calendar must be from a list of configured values

			//globe-coordinate
			[ 'globe-coordinate', 'Foo', false, 'GlobeCoordinateValue expected, string supplied' ],
			[ 'globe-coordinate', new NumberValue( 7 ), false, 'GlobeCoordinateValue expected' ],

			//globe-coordinate[precision]
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true,
				'integer precision is valid'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 0.2, $wikidataUri . 'Q2' ),
				true,
				'float precision is valid'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, null, $wikidataUri . 'Q2' ),
				false,
				'null precision is invalid'
			],

			//globe-coordinate[globe]
			// FIXME: this is testing unimplemented behaviour? Probably broken...
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, '' ),
				false,
				'globe: empty string should be invalid'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false,
				'globe: too long'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true,
				'globe: URL'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' ' . $wikidataUri . 'Q2 ' ),
				false,
				'globe: untrimmed'
			],
			[
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' javascript:alert(1) ' ),
				false,
				'globe: bad URL scheme'
			],
			//TODO: globe must be an item reference
			//TODO: globe must be from a list of configured values

			// url
			[ 'url', 'Foo', false, 'StringValue expected, string supplied' ],
			[ 'url', new NumberValue( 7 ), false, 'StringValue expected' ],

			[ 'url', new StringValue( 'http://acme.com' ), true, 'Simple HTTP URL' ],
			[ 'url', new StringValue( 'https://acme.com' ), true, 'Simple HTTPS URL' ],
			[ 'url', new StringValue( 'ftp://acme.com' ), true, 'Simple FTP URL' ],
			[ 'url', new StringValue( 'http://acme.com/foo/bar?some=stuff#fragment' ), true, 'Complex HTTP URL' ],

			// evil url
			[ 'url', new StringValue( '//bla' ), false, 'Protocol-relative' ],
			[ 'url', new StringValue( '/bla/bla' ), false, 'relative path' ],
			[ 'url', new StringValue( 'just stuff' ), false, 'just words' ],
			[ 'url', new StringValue( 'javascript:alert("evil")' ), false, 'JavaScript URL' ],
			[ 'url', new StringValue( 'http://' ), false, 'bad http URL' ],
			[ 'url', new StringValue( 'http://' . str_repeat( 'x', 494 ) ), false, 'URL too long' ],

			[ 'url', new StringValue( ' http://acme.com' ), false, 'URL with leading space' ],
			[ 'url', new StringValue( 'http://acme.com ' ), false, 'URL with trailing space' ],

			//quantity
			[ 'quantity', UnboundedQuantityValue::newFromNumber( 5 ), true, 'Unbounded' ],
			[ 'quantity', QuantityValue::newFromNumber( 5 ), true, 'Simple integer' ],
			[ 'quantity', QuantityValue::newFromNumber( 5, 'http://qudt.org/vocab/unit#Meter' ), true, 'Vocabulary URI' ],
			[ 'quantity', QuantityValue::newFromNumber( 5, $wikidataUri . 'Q11573' ), false, 'Wikidata URI' ],
			[ 'quantity', QuantityValue::newFromNumber( 5, '1' ), true, '1 means unitless' ],
			[ 'quantity', QuantityValue::newFromNumber( 5, 'kittens' ), false, 'Bad unit URI' ],
			[ 'quantity', QuantityValue::newFromNumber( '-11.234', '1', '-10', '-12' ), true, 'decimal strings' ],

			//monolingual text
			[ 'monolingualtext', new MonolingualTextValue( 'contentlanguage', 'text' ), true, 'Simple value' ],
			[ 'monolingualtext', new MonolingualTextValue( 'en', 'text' ), false, 'Not a valid language' ],
		];

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypeValidation
	 */
	public function testDataTypeValidation( $typeId, $value, $expected, $message ) {
		$builders = $this->newValidatorBuilders();

		$validatorMap = [
			'commonsMedia'      => [ $builders, 'buildMediaValidators' ],
			'globe-coordinate'  => [ $builders, 'buildCoordinateValidators' ],
			'monolingualtext'   => [ $builders, 'buildMonolingualTextValidators' ],
			'quantity'          => [ $builders, 'buildQuantityValidators' ],
			'string'            => [ $builders, 'buildStringValidators' ],
			'time'              => [ $builders, 'buildTimeValidators' ],
			'url'               => [ $builders, 'buildUrlValidators' ],
			'wikibase-entity'   => [ $builders, 'buildEntityValidators' ],
			'wikibase-item'     => [ $builders, 'buildItemValidators' ],
			'wikibase-property' => [ $builders, 'buildPropertyValidators' ],
		];

		$validators = call_user_func( $validatorMap[$typeId] );

		$this->assertValidation( $expected, $validators, $value, $message );
	}

	/**
	 * @param bool $expected
	 * @param ValueValidator[] $validators
	 * @param mixed $value
	 * @param string $message
	 */
	protected function assertValidation( $expected, array $validators, $value, $message ) {
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
				$this->fail( $message . "\n" . $errors[0]->getText() );
			}

			$this->assertEquals( $expected, $result->isValid(), $message );
		} else {
			$this->assertEquals( $expected, $result->isValid(), $message );
		}
	}

}
