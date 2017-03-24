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
use MediaWiki\Site\MediaWikiPageNameNormalizer;

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

	const GEO_SHAPE_STORAGE_API_URL = 'http://some.wiki/api.php';

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
			],
			$this->getMediaWikiPageNameNormalizer(),
			self::GEO_SHAPE_STORAGE_API_URL
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
	 * @return MediaWikiPageNameNormalizer
	 */
	private function getMediaWikiPageNameNormalizer() {
		$pageNormalizer = $this->getMockBuilder( MediaWikiPageNameNormalizer::class )
			->disableOriginalConstructor()
			->getMock();

		$pageNormalizer->expects( $this->any() )
			->method( 'normalizePageName' )
			->will( $this->returnCallback( function( $pageName ) {
				return strpos( $pageName, 'NOT-FOUND' ) === false ? $pageName : false;
			} ) );

		return $pageNormalizer;
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

	public function provideCommonsMediaValidation() {
		return [
			'Should not be empty' => [ '', false ],
			'Too long' => [ str_repeat( 'x', 237 ) . '.jpg', false ],
			'Should have extension' => [ 'Foo', false ],
			'Extension to short' => [ 'Foo.a', false ],
			'This should be good' => [ 'Foo.jpg', true ],
			'Illegal character: newline' => [ "a\na.jpg", false ],
			'Illegal character: open square bracket' => [ 'a[a.jpg', false ],
			'Illegal character: close square bracket' => [ 'a]a.jpg', false ],
			'Illegal character: open curly bracket' => [ 'a{a.jpg', false ],
			'Illegal character: close curly bracket' => [ 'a}a.jpg', false ],
			'Illegal character: pipe' => [ 'a|a.jpg', false ],
			'Illegal character: hash' => [ 'Foo#bar.jpg', false ],
			'Illegal character: colon' => [ 'Foo:bar.jpg', false ],
			'Illegal character: slash' => [ 'Foo/bar.jpg', false ],
			'Illegal character: backslash' => [ 'Foo\bar.jpg', false ],
			'Unicode support' => [ 'Äöü.jpg', true ],
			'Leading space' => [ ' Foo.jpg', false ],
			'Trailing space' => [ 'Foo.jpg ', false ],
			'Not found' => [ 'Foo-NOT-FOUND.jpg', false ],
		];
	}

	/**
	 * @dataProvider provideCommonsMediaValidation
	 */
	public function testCommonsMediaValidation( $fileName, $expected ) {
		$value = new StringValue( $fileName );
		$validators = $this->newValidatorBuilders()->buildMediaValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideGeoShapeValidation() {
		return [
			'Should not be empty' => [ '', false ],
			'Too long' => [ 'Data:' . str_repeat( 'x', 232 ) . '.map', false ],
			'Should have extension' => [ 'Data:Foo', false ],
			'Extension too short' => [ 'Data:Foo.a', false ],
			'This should be good' => [ 'Data:Foo.map', true ],
			'Should have Data namespace' => [ 'Foo.map', false ],
			'Illegal character: newline' => [ "Data:a\na.map", false ],
			'Illegal character: open square bracket' => [ 'Data:a[a.map', false ],
			'Illegal character: close square bracket' => [ 'Data:a]a.map', false ],
			'Illegal character: open curly bracket' => [ 'Data:a{a.map', false ],
			'Illegal character: close curly bracket' => [ 'Data:a}a.map', false ],
			'Illegal character: pipe' => [ 'Data:a|a.map', false ],
			'Illegal character: hash' => [ 'Data:Foo#bar.map', false ],
			'Illegal character: colon' => [ 'Data:Foo:bar.map', false ],
			'Allowed character: slash' => [ 'Data:Foo/bar.map', true ],
			'Illegal character: backslash' => [ 'Data:Foo\bar.map', false ],
			'Unicode support' => [ 'Data:Äöü.map', true ],
			'Leading space' => [ ' Data:Foo.map', false ],
			'Trailing space' => [ 'Data:Foo.map ', false ],
			'Not found' => [ 'Data:Foo-NOT-FOUND.map', false ],
		];
	}

	/**
	 * @dataProvider provideGeoShapeValidation
	 */
	public function testGeoShapeValidation( $geoShapeTitle, $expected ) {
		$value = new StringValue( $geoShapeTitle );
		$validators = $this->newValidatorBuilders()->buildGeoShapeValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideGlobeCoordinateValueValidation() {
		$wikidataUri = 'http://www.wikidata.org/entity/';

		return [
			'Integer precision is valid' => [ 1, $wikidataUri . 'Q2', true ],
			'Float precision is valid' => [ 0.2, $wikidataUri . 'Q2', true ],
			'Null precision is invalid' => [ null, $wikidataUri . 'Q2', false ],

			// FIXME: This is testing unimplemented behaviour? Probably broken...
			'Globe should not be empty' => [ 1, '', false ],
			'Globe too long' => [ 1, $wikidataUri . 'Q' . str_repeat( '6', 224 ), false ],
			'Valid globe' => [ 1, $wikidataUri . 'Q2', true ],
			'Untrimmed globe' => [ 1, ' ' . $wikidataUri . 'Q2 ', false ],
			'Bad URL scheme' => [ 1, ' javascript:alert(1) ', false ],

			// TODO: Globe must be an item reference
			// TODO: Globe must be from a list of configured values
		];
	}

	/**
	 * @dataProvider provideGlobeCoordinateValueValidation
	 */
	public function testGlobeCoordinateValueValidation( $precision, $globe, $expected ) {
		$value = new GlobeCoordinateValue( new LatLongValue( 0, 0 ), $precision, $globe );
		$validators = $this->newValidatorBuilders()->buildCoordinateValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideStringValueValidation() {
		return [
			'Space' => [ 'x x', true ],
			'Unicode support' => [ 'Äöü', true ],
			'T161263' => [ 'Ӆ', true ],

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
			'U+0085: Next line' => [ "x\xC2\x85x", false ],
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

	public function provideTimeValueValidation() {
		$wikidataUri = 'http://www.wikidata.org/entity/';

		return [
			'Calendar model is not a URL' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_DAY,
				'1',
				false
			],
			'Calendar model too long' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$wikidataUri . 'Q' . str_repeat( '6', 224 ),
				false
			],
			'Valid calendar model' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_DAY,
				$wikidataUri . 'Q1985727',
				true
			],
			'Untrimmed calendar model' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_DAY,
				' ' . $wikidataUri . 'Q1985727 ',
				false
			],
			'Bad URL scheme' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_DAY,
				' javascript:alert(1)',
				false
			],

			'Values more precise than a day are currently not allowed' => [
				'+2013-06-06T11:22:33Z',
				TimeValue::PRECISION_DAY,
				$wikidataUri . 'Q1985727',
				false
			],
			'Precisions more fine-grained than a day are currently not allowed' => [
				'+2013-06-06T00:00:00Z',
				TimeValue::PRECISION_SECOND,
				$wikidataUri . 'Q1985727',
				false
			],

			// TODO: Calendar must be an item reference
			// TODO: Calendar must be from a list of configured values
		];
	}

	/**
	 * @dataProvider provideTimeValueValidation
	 */
	public function testTimeValueValidation( $timestamp, $precision, $calendarModel, $expected ) {
		$value = new TimeValue( $timestamp, 0, 0, 0, $precision, $calendarModel );
		$validators = $this->newValidatorBuilders()->buildTimeValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideUrlValidation() {
		return [
			'Simple HTTP URL' => [ 'http://acme.com', true ],
			'Simple HTTPS URL' => [ 'https://acme.com', true ],
			'Simple FTP URL' => [ 'ftp://acme.com', true ],
			'Complex HTTP URL' => [ 'http://acme.com/foo/bar?some=stuff#fragment', true ],

			// Evil URLs
			'Protocol-relative' => [ '//bla', false ],
			'Relative path' => [ '/bla/bla', false ],
			'Just words' => [ 'just stuff', false ],
			'JavaScript' => [ 'javascript:alert("evil")', false ],
			'Bad HTTP URL' => [ 'http://', false ],
			'Too long' => [ 'http://' . str_repeat( 'x', 494 ), false ],

			'Leading space' => [ ' http://acme.com', false ],
			'Trailing space' => [ 'http://acme.com ', false ],
		];
	}

	/**
	 * @dataProvider provideUrlValidation
	 */
	public function testUrlValidation( $string, $expected ) {
		$value = new StringValue( $string );
		$validators = $this->newValidatorBuilders()->buildUrlValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

>>>>>>> bd57dce... Change bad ASCII to UTF-8 validation in terms/value validators
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
			array( 'wikibase-entity', new EntityIdValue( new ItemId( 'bar:Q123' ) ), false, 'unknown repository' ),
			array( 'wikibase-entity', new EntityIdValue( new ItemId( 'foo:Q123' ) ), true, 'foreign entity' ),
			array( 'wikibase-entity', new EntityIdValue( new PropertyId( 'foo:P42' ) ), false, 'unsupported foreign entity type' ),
			array( 'wikibase-entity', new StringValue( 'q8' ), false, 'Expected EntityId, StringValue supplied' ),

			//commonsMedia
			array( 'commonsMedia', 'Foo.jpg', false, 'StringValue expected, string supplied' ),
			array( 'commonsMedia', new NumberValue( 7 ), false, 'StringValue expected' ),

			//geo-shape
			array( 'geo-shape', 'Foo.map', false, 'StringValue expected, string supplied' ),
			array( 'geo-shape', new NumberValue( 7 ), false, 'StringValue expected' ),

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
			array( 'quantity', UnboundedQuantityValue::newFromNumber( 5 ), true, 'Unbounded' ),
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
			'geo-shape'         => array( $builders, 'buildGeoShapeValidators' ),
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
	protected function assertValidation( $expected, array $validators, $value, $message = '' ) {
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
