<?php

namespace Wikibase\Test;

use DataValues\Geo\Values\GlobeCoordinateValue;
use DataValues\Geo\Values\LatLongValue;
use DataValues\MonolingualTextValue;
use DataValues\NumberValue;
use DataValues\QuantityValue;
use DataValues\StringValue;
use DataValues\TimeValue;
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

		$urlSchemes = array( 'http', 'https', 'ftp', 'mailto' );

		$builders = new ValidatorBuilders(
			$entityLookup,
			$entityIdParser,
			$urlSchemes,
			'http://qudt.org/vocab/',
			new StaticContentLanguages( array( 'contentlanguage' ) ),
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

		$cases = array(
			//wikibase-item
			array( 'wikibase-item', 'q8', false, 'Expected EntityId, string supplied' ),
			array( 'wikibase-item', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ),
			array( 'wikibase-item', new EntityIdValue( new ItemId( 'q8' ) ), true, 'existing entity' ),
			array( 'wikibase-item', new EntityIdValue( new ItemId( 'q3' ) ), false, 'missing entity' ),
			array( 'wikibase-item', new EntityIdValue( new PropertyId( 'p8' ) ), false, 'not an item' ),

			// wikibase-property
			array( 'wikibase-property', new EntityIdValue( new PropertyId( 'p8' ) ), true, 'existing entity' ),
			array( 'wikibase-property', new EntityIdValue( new ItemId( 'q8' ) ), false, 'not a property' ),

			// generic wikibase entity
			array( 'wikibase-entity', new EntityIdValue( new PropertyId( 'p8' ) ), true, 'existing entity' ),
			array( 'wikibase-entity', new EntityIdValue( new ItemId( 'q8' ) ), true, 'existing entity' ),
			array( 'wikibase-entity', new EntityIdValue( new ItemId( 'q3' ) ), false, 'missing entity' ),
			array( 'wikibase-entity', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ),

			//commonsMedia
			array( 'commonsMedia', 'Foo.jpg', false, 'StringValue expected, string supplied' ),
			array( 'commonsMedia', new NumberValue( 7 ), false, 'StringValue expected' ),
			array( 'commonsMedia', new StringValue( '' ), false, 'empty string should be invalid' ),
			array( 'commonsMedia', new StringValue( str_repeat( 'x', 237 ) . '.jpg' ), false, 'name too long' ),
			array( 'commonsMedia', new StringValue( 'Foo' ), false, 'no file extension' ),
			array( 'commonsMedia', new StringValue( 'Foo.a' ), false, 'file extension to short' ),
			array( 'commonsMedia', new StringValue( 'Foo.jpg' ), true, 'this should be good' ),
			array( 'commonsMedia', new StringValue( "a\na.jpg" ), false, 'illegal character: newline' ),
			array( 'commonsMedia', new StringValue( 'a[a.jpg' ), false, 'illegal character: square bracket' ),
			array( 'commonsMedia', new StringValue( 'a]a.jpg' ), false, 'illegal character: square bracket' ),
			array( 'commonsMedia', new StringValue( 'a{a.jpg' ), false, 'illegal character: curly bracket' ),
			array( 'commonsMedia', new StringValue( 'a}a.jpg' ), false, 'illegal character: curly bracket' ),
			array( 'commonsMedia', new StringValue( 'a|a.jpg' ), false, 'illegal character: pipe' ),
			array( 'commonsMedia', new StringValue( 'Foo#bar.jpg' ), false, 'illegal character: hash' ),
			array( 'commonsMedia', new StringValue( 'Foo:bar.jpg' ), false, 'illegal character: colon' ),
			array( 'commonsMedia', new StringValue( 'Foo/bar.jpg' ), false, 'illegal character: slash' ),
			array( 'commonsMedia', new StringValue( 'Foo\bar.jpg' ), false, 'illegal character: backslash' ),
			array( 'commonsMedia', new StringValue( 'Äöü.jpg' ), true, 'Unicode support' ),
			array( 'commonsMedia', new StringValue( ' Foo.jpg' ), false, 'media name with leading space' ),
			array( 'commonsMedia', new StringValue( 'Foo.jpg ' ), false, 'media name with trailing space' ),
			array( 'commonsMedia', new StringValue( 'Foo-NOT-FOUND.jpg' ), false, 'file not found' ),

			//string
			array( 'string', 'Foo', false, 'StringValue expected, string supplied' ),
			array( 'string', new NumberValue( 7 ), false, 'StringValue expected' ),
			array( 'string', new StringValue( '' ), false, 'empty string should be invalid' ),
			array( 'string', new StringValue( 'Foo' ), true, 'simple string' ),
			array( 'string', new StringValue( 'Äöü' ), true, 'Unicode support' ),
			array( 'string', new StringValue( str_repeat( 'x', 400 ) ), true, 'long, but not too long' ),
			array( 'string', new StringValue( str_repeat( 'x', 401 ) ), false, 'too long' ),
			array( 'string', new StringValue( ' Foo' ), false, 'string with leading space' ),
			array( 'string', new StringValue( 'Foo ' ), false, 'string with trailing space' ),

			//time
			array( 'time', 'Foo', false, 'TimeValue expected, string supplied' ),
			array( 'time', new NumberValue( 7 ), false, 'TimeValue expected' ),

			//time['calendar-model']
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY, '1' ),
				false,
				'calendar: too short'
			),
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false,
				'calendar: too long'
			),
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				true,
				'calendar: URL'
			),
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' ' . $wikidataUri . 'Q1985727 ' ),
				false,
				'calendar: untrimmed'
			),
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					' javascript:alert(1)' ),
				false,
				'calendar: bad URL'
			),

			//precision to the second (currently not allowed)
			array(
				'time',
				new TimeValue( '+2013-06-06T11:22:33Z', 0, 0, 0, TimeValue::PRECISION_DAY,
					$wikidataUri . 'Q1985727' ),
				false,
				'time given to the second'
			),
			array(
				'time',
				new TimeValue( '+2013-06-06T00:00:00Z', 0, 0, 0, TimeValue::PRECISION_SECOND,
					$wikidataUri . 'Q1985727' ),
				false,
				'precision: second'
			),

			//TODO: calendar must be an item reference
			//TODO: calendar must be from a list of configured values

			//globe-coordinate
			array( 'globe-coordinate', 'Foo', false, 'GlobeCoordinateValue expected, string supplied' ),
			array( 'globe-coordinate', new NumberValue( 7 ), false, 'GlobeCoordinateValue expected' ),

			//globe-coordinate[precision]
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true,
				'integer precision is valid'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 0.2, $wikidataUri . 'Q2' ),
				true,
				'float precision is valid'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, null, $wikidataUri . 'Q2' ),
				false,
				'null precision is invalid'
			),

			//globe-coordinate[globe]
			// FIXME: this is testing unimplemented behaviour? Probably broken...
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, '' ),
				false,
				'globe: empty string should be invalid'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q' . str_repeat( '6', 224 ) ),
				false,
				'globe: too long'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, $wikidataUri . 'Q2' ),
				true,
				'globe: URL'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' ' . $wikidataUri . 'Q2 ' ),
				false,
				'globe: untrimmed'
			),
			array(
				'globe-coordinate',
				new GlobeCoordinateValue( $latLonValue, 1, ' javascript:alert(1) ' ),
				false,
				'globe: bad URL scheme'
			),
			//TODO: globe must be an item reference
			//TODO: globe must be from a list of configured values

			// url
			array( 'url', 'Foo', false, 'StringValue expected, string supplied' ),
			array( 'url', new NumberValue( 7 ), false, 'StringValue expected' ),

			array( 'url', new StringValue( 'http://acme.com' ), true, 'Simple HTTP URL' ),
			array( 'url', new StringValue( 'https://acme.com' ), true, 'Simple HTTPS URL' ),
			array( 'url', new StringValue( 'ftp://acme.com' ), true, 'Simple FTP URL' ),
			array( 'url', new StringValue( 'http://acme.com/foo/bar?some=stuff#fragment' ), true, 'Complex HTTP URL' ),

			// evil url
			array( 'url', new StringValue( '//bla' ), false, 'Protocol-relative' ),
			array( 'url', new StringValue( '/bla/bla' ), false, 'relative path' ),
			array( 'url', new StringValue( 'just stuff' ), false, 'just words' ),
			array( 'url', new StringValue( 'javascript:alert("evil")' ), false, 'JavaScript URL' ),
			array( 'url', new StringValue( 'http://' ), false, 'bad http URL' ),
			array( 'url', new StringValue( 'http://' . str_repeat( 'x', 494 ) ), false, 'URL too long' ),

			array( 'url', new StringValue( ' http://acme.com' ), false, 'URL with leading space' ),
			array( 'url', new StringValue( 'http://acme.com ' ), false, 'URL with trailing space' ),

			//quantity
			array( 'quantity', QuantityValue::newFromNumber( 5 ), true, 'Simple integer' ),
			array( 'quantity', QuantityValue::newFromNumber( 5, 'http://qudt.org/vocab/unit#Meter' ), true, 'Vocabulary URI' ),
			array( 'quantity', QuantityValue::newFromNumber( 5, $wikidataUri . 'Q11573' ), false, 'Wikidata URI' ),
			array( 'quantity', QuantityValue::newFromNumber( 5, '1' ), true, '1 means unitless' ),
			array( 'quantity', QuantityValue::newFromNumber( 5, 'kittens' ), false, 'Bad unit URI' ),
			array( 'quantity', QuantityValue::newFromNumber( '-11.234', '1', '-10', '-12' ), true, 'decimal strings' ),

			//monolingual text
			array( 'monolingualtext', new MonolingualTextValue( 'contentlanguage', 'text' ), true, 'Simple value' ),
			array( 'monolingualtext', new MonolingualTextValue( 'en', 'text' ), false, 'Not a valid language' ),
		);

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypeValidation
	 */
	public function testDataTypeValidation( $typeId, $value, $expected, $message ) {
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
