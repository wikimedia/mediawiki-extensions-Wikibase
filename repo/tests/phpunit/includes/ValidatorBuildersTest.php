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

	const TABULAR_DATA_STORAGE_API_URL = 'http://another.wiki/api.php';

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
			self::GEO_SHAPE_STORAGE_API_URL,
			self::TABULAR_DATA_STORAGE_API_URL
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

	public function provideTabularDataValidation() {
		return [
			'Should not be empty' => [ '', false ],
			'Too long' => [ 'Data:' . str_repeat( 'x', 232 ) . '.tab', false ],
			'Should have extension' => [ 'Data:Foo', false ],
			'Extension too short' => [ 'Data:Foo.a', false ],
			'This should be good' => [ 'Data:Foo.tab', true ],
			'Should have Data namespace' => [ 'Foo.tab', false ],
			'Illegal character: newline' => [ "Data:a\na.tab", false ],
			'Illegal character: open square bracket' => [ 'Data:a[a.tab', false ],
			'Illegal character: close square bracket' => [ 'Data:a]a.tab', false ],
			'Illegal character: open curly bracket' => [ 'Data:a{a.tab', false ],
			'Illegal character: close curly bracket' => [ 'Data:a}a.tab', false ],
			'Illegal character: pipe' => [ 'Data:a|a.tab', false ],
			'Illegal character: hash' => [ 'Data:Foo#bar.tab', false ],
			'Illegal character: colon' => [ 'Data:Foo:bar.tab', false ],
			'Allowed character: slash' => [ 'Data:Foo/bar.tab', true ],
			'Illegal character: backslash' => [ 'Data:Foo\bar.tab', false ],
			'Unicode support' => [ 'Data:Äöü.tab', true ],
			'Leading space' => [ ' Data:Foo.tab', false ],
			'Trailing space' => [ 'Data:Foo.tab ', false ],
			'Not found' => [ 'Data:Foo-NOT-FOUND.tab', false ],
		];
	}

	/**
	 * @dataProvider provideTabularDataValidation
	 */
	public function testTabularDataValidation( $tabularDataTitle, $expected ) {
		$value = new StringValue( $tabularDataTitle );
		$validators = $this->newValidatorBuilders()->buildTabularDataValidators();

		$this->assertValidation( $expected, $validators, $value );
	}

	public function provideGlobeCoordinateValueValidation() {
		$wikidataUri = 'http://www.wikidata.org/entity/';

		return [
			'Integer precision is valid' => [ 1, $wikidataUri . 'Q2', true ],
			'Float precision is valid' => [ 0.2, $wikidataUri . 'Q2', true ],
			'Null precision is invalid' => [ null, $wikidataUri . 'Q2', false ],

			'Globe must be a URI' => [ 1, 'Earth', false ],
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

	public function provideDataTypeValidation() {
		$wikidataUri = 'http://www.wikidata.org/entity/';

		$cases = [
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

			//geo-shape
			'GeoShape expected StringValue, string supplied' => [ 'geo-shape', 'Foo.map', false ],
			'GeoShape expected StringValue, NumberValue supplied' => [ 'geo-shape', new NumberValue( 7 ), false ],

			//tabular-data
			'TabularData expected StringValue, string supplied' => [
				'tabular-data',
				'Foo.tab',
				false
			],
			'TabularData expected StringValue, NumberValue supplied' => [
				'tabular-data',
				new NumberValue( 7 ),
				false
			],

			//string
			'String expects StringValue, got string' => [ 'string', 'Foo', false ],
			'String expects StringValue' => [ 'string', new NumberValue( 7 ), false ],

			//time
			'TimeValue expected, string supplied' => [ 'time', 'Foo', false ],
			'TimeValue expected' => [ 'time', new NumberValue( 7 ), false ],

			//globe-coordinate
			'GlobeCoordinateValue expected, string supplied' => [ 'globe-coordinate', 'Foo', false ],
			'GlobeCoordinateValue expected' => [ 'globe-coordinate', new NumberValue( 7 ), false ],

			// url
			'URL expects StringValue, got string' => [ 'url', 'Foo', false ],
			'URL expects StringValue' => [ 'url', new NumberValue( 7 ), false ],

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
		];

		return $cases;
	}

	/**
	 * @dataProvider provideDataTypeValidation
	 */
	public function testDataTypeValidation( $typeId, $value, $expected ) {
		$builders = $this->newValidatorBuilders();

		$validatorMap = [
			'commonsMedia'      => [ $builders, 'buildMediaValidators' ],
			'geo-shape'         => [ $builders, 'buildGeoShapeValidators' ],
			'globe-coordinate'  => [ $builders, 'buildCoordinateValidators' ],
			'monolingualtext'   => [ $builders, 'buildMonolingualTextValidators' ],
			'quantity'          => [ $builders, 'buildQuantityValidators' ],
			'string'            => [ $builders, 'buildStringValidators' ],
			'tabular-data'      => [ $builders, 'buildTabularDataValidators' ],
			'time'              => [ $builders, 'buildTimeValidators' ],
			'url'               => [ $builders, 'buildUrlValidators' ],
			'wikibase-entity'   => [ $builders, 'buildEntityValidators' ],
			'wikibase-item'     => [ $builders, 'buildItemValidators' ],
			'wikibase-property' => [ $builders, 'buildPropertyValidators' ],
		];

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
