<?php

namespace Wikibase\Repo\Tests\ChangeOp;

use DataValues\DataValue;
use DataValues\NumberValue;
use DataValues\StringValue;
use OutOfBoundsException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use ValueValidators\Error;
use ValueValidators\Result;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\NumericPropertyId;
use Wikibase\DataModel\Services\Lookup\InMemoryDataTypeLookup;
use Wikibase\DataModel\Services\Lookup\PropertyDataTypeLookup;
use Wikibase\DataModel\Services\Statement\StatementGuidParser;
use Wikibase\DataModel\Services\Statement\StatementGuidValidator;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\DataModel\Statement\StatementGuid;
use Wikibase\Lib\DataType;
use Wikibase\Lib\DataTypeFactory;
use Wikibase\Lib\Normalization\DataValueNormalizer;
use Wikibase\Lib\Normalization\ReferenceNormalizer;
use Wikibase\Lib\Normalization\SnakNormalizer;
use Wikibase\Lib\Normalization\StatementNormalizer;
use Wikibase\Repo\DataTypeValidatorFactory;
use Wikibase\Repo\Store\SiteLinkConflictLookup;
use Wikibase\Repo\Validators\CompositeValidator;
use Wikibase\Repo\Validators\DataValueValidator;
use Wikibase\Repo\Validators\LabelDescriptionNotEqualValidator;
use Wikibase\Repo\Validators\RegexValidator;
use Wikibase\Repo\Validators\SnakValidator;
use Wikibase\Repo\Validators\TermValidatorFactory;
use Wikibase\Repo\Validators\TypeValidator;

/**
 * A helper class for test cases that deal with claims.
 * Provides mock services frequently used with claims.
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class ChangeOpTestMockProvider {

	/**
	 * @var TestCase
	 */
	private $mockBuilderFactory;

	public function __construct( TestCase $mockBuilderFactory ) {
		$this->mockBuilderFactory = $mockBuilderFactory;
	}

	/**
	 * @see TestCase::createMock
	 *
	 * @param string $class
	 *
	 * @return MockObject
	 */
	private function createMock( $class ) {
		return $this->mockBuilderFactory->getMockBuilder( $class )
			->disableOriginalConstructor()
			->disableOriginalClone()
			->disableArgumentCloning()
			->disallowMockingUnknownTypes()
			->getMock();
	}

	/**
	 * Convenience method for creating Statements.
	 *
	 * @param string|NumericPropertyId $propertyId
	 *
	 * @param string|int|float|DataValue|null $value The value of the new
	 *        claim's main snak. Null will result in a PropertyNoValueSnak.
	 *
	 * @return Statement A new statement with a main snak based on the parameters provided.
	 */
	public function makeStatement( $propertyId, $value = null ) {
		if ( is_string( $value ) ) {
			$value = new StringValue( $value );
		} elseif ( is_int( $value ) || is_float( $value ) ) {
			$value = new NumberValue( $value );
		}

		if ( is_string( $propertyId ) ) {
			$propertyId = new NumericPropertyId( $propertyId );
		}

		if ( $value === null ) {
			$snak = new PropertyNoValueSnak( $propertyId );
		} else {
			$snak = new PropertyValueSnak( $propertyId, $value );
		}

		return new Statement( $snak );
	}

	/**
	 * Returns a mock StatementGuidValidator that accepts any GUID.
	 *
	 * @return StatementGuidValidator
	 */
	public function getMockGuidValidator() {
		$mock = $this->createMock( StatementGuidValidator::class );
		$mock->method( 'validate' )
			->willReturn( true );
		$mock->method( 'validateFormat' )
			->willReturn( true );
		return $mock;
	}

	/**
	 * Returns a mock SnakValidator based on getMockPropertyDataTypeLookup()
	 * and getMockDataTypeFactory(), which will accept snaks containing a StringValue
	 * that is not "INVALID".
	 *
	 * @return SnakValidator
	 */
	public function getMockSnakValidator() {
		return new SnakValidator(
			$this->getMockPropertyDataTypeLookup(),
			$this->getMockDataTypeFactory(),
			$this->getMockDataTypeValidatorFactory()
		);
	}

	/**
	 * Returns a mock PropertyDataTypeLookup that will return the
	 * type id "string" for any property.
	 *
	 * @return PropertyDataTypeLookup
	 */
	public function getMockPropertyDataTypeLookup() {
		$mock = $this->createMock( PropertyDataTypeLookup::class );
		$mock->method( 'getDataTypeIdForProperty' )
			->willReturn( 'string' );

		return $mock;
	}

	/**
	 * Returns a mock MockDataTypeFactory that will return the same DataType for
	 * any type id.
	 *
	 * @return DataTypeFactory
	 */
	public function getMockDataTypeFactory() {
		$stringType = new DataType( 'string', 'string' );

		$types = [
			'string' => $stringType,
		];

		$mock = $this->createMock( DataTypeFactory::class );
		$mock->method( 'getType' )
			->willReturnCallback( function( $id ) use ( $types ) {
				if ( !isset( $types[$id] ) ) {
					throw new OutOfBoundsException( "No such type: $id" );
				}

				return $types[$id];
			} );

		return $mock;
	}

	/**
	 * Returns a mock DataTypeValidatorFactory that returns validators which will accept any
	 * StringValue, unless the string is "INVALID".
	 *
	 * @return DataTypeValidatorFactory
	 */
	public function getMockDataTypeValidatorFactory() {
		// consider "INVALID" to be invalid
		$topValidator = new DataValueValidator(
			new CompositeValidator( [
				new TypeValidator( 'string' ),
				new RegexValidator( '/INVALID/', true ),
			], true )
		);

		$validators = [ new TypeValidator( DataValue::class ), $topValidator ];

		$mock = $this->createMock( DataTypeValidatorFactory::class );
		$mock->method( 'getValidators' )
			->willReturnCallback( function( $id ) use ( $validators ) {
				return $validators;
			} );

		return $mock;
	}

	/**
	 * Returns a mock validator. The term and the language "INVALID" is considered to be
	 * invalid.
	 *
	 * @return ValueValidator
	 */
	public function getMockTermValidator() {
		$mock = $this->createMock( ValueValidator::class );
		$mock->method( 'validate' )
			->willReturnCallback( function( $text ) {
				if ( $text === 'INVALID' ) {
					$error = Error::newError( 'Invalid', '', 'test-invalid' );
					return Result::newError( [ $error ] );
				} else {
					return Result::newSuccess();
				}
			} );

		return $mock;
	}

	/**
	 * Returns a mock StatementGuidParser that will return the same ClaimGuid for
	 * all input strings.
	 *
	 * @param EntityId $entityId
	 *
	 * @return StatementGuidParser
	 */
	public function getMockGuidParser( EntityId $entityId ) {
		$guid = $this->createMock( StatementGuid::class );
		$guid->method( 'getSerialization' )
			->willReturn( 'theValidatorIsMockedSoMeh! :D' );
		$guid->method( 'getEntityId' )
			->willReturn( $entityId );

		$mock = $this->createMock( StatementGuidParser::class );
		$mock->method( 'parse' )
			->willReturn( $guid );
		return $mock;
	}

	public function detectLabelConflicts(
		$entityType,
		array $labels,
		array $aliases = null,
		EntityId $entityId = null
	) {
		if ( $entityId && $entityId->getSerialization() === 'P666' ) {
			// simulated conflicts always conflict with P666, so if these are
			// ignored as self-conflicts, we don't need to check any labels.
			$labels = [];
		}

		foreach ( $labels as $lang => $text ) {
			if ( $text === 'DUPE' ) {
				return Result::newError( [
					Error::newError(
						'found conflicting terms',
						'label',
						'label-conflict',
						[
							'label',
							$lang,
							$text,
							'P666',
						]
					),
				] );
			}
		}

		if ( $aliases === null ) {
			return Result::newSuccess();
		}

		foreach ( $aliases as $lang => $texts ) {
			if ( in_array( 'DUPE', $texts ) ) {
				return Result::newError( [
					Error::newError(
						'found conflicting terms',
						'alias',
						'label-conflict',
						[
							'alias',
							$lang,
							'DUPE',
							'P666',
						]
					),
				] );
			}
		}

		return Result::newSuccess();
	}

	/**
	 * @return SiteLinkConflictLookup
	 */
	public function getMockSiteLinkConflictLookup() {
		$mock = $this->createMock( SiteLinkConflictLookup::class );

		$mock->method( 'getConflictsForItem' )
			->willReturnCallback( function ( Item $item ) {
				$conflicts = [];

				foreach ( $item->getSiteLinkList()->toArray() as $link ) {
					if ( $link->getPageName() === 'DUPE' ) {
						$conflicts[] = [
							'siteId' => $link->getSiteId(),
							'itemId' => new ItemId( 'Q666' ),
							'sitePage' => $link->getPageName(),
						];
					} elseif ( $link->getPageName() === 'DUPE-UNKNOWN' ) {
						$conflicts[] = [
							'siteId' => $link->getSiteId(),
							'itemId' => null,
							'sitePage' => $link->getPageName(),
						];
					}
				}

				return $conflicts;
			} );

		return $mock;
	}

	/**
	 * @return LabelDescriptionNotEqualValidator
	 */
	public function getLabelDescriptionNotEqualValidator() {
		return new LabelDescriptionNotEqualValidator();
	}

	/**
	 * Returns a TermValidatorFactory that provides mock validators.
	 * The validators consider the string "INVALID" to be invalid, and "DUPE" to be duplicates.
	 *
	 * @see getMockTermValidator()
	 * @see getLabelDescriptionNotEqualValidator()
	 *
	 * @return TermValidatorFactory
	 */
	public function getMockTermValidatorFactory() {
		$mock = $this->createMock( TermValidatorFactory::class );

		$mock->method( 'getLabelDescriptionNotEqualValidator' )
			->willReturnCallback(
				[ $this, 'getLabelDescriptionNotEqualValidator' ]
			);

		$mock->method( 'getLabelLanguageValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		$mock->method( 'getDescriptionLanguageValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		$mock->method( 'getAliasLanguageValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		$mock->method( 'getLabelValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		$mock->method( 'getDescriptionValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		$mock->method( 'getAliasValidator' )
			->willReturnCallback(
				[ $this, 'getMockTermValidator' ]
			);

		return $mock;
	}

	/** A mock snak normalizer which uppercases all string values. */
	public function getMockSnakNormalizer(): SnakNormalizer {
		return new SnakNormalizer( new InMemoryDataTypeLookup(), new NullLogger(), [
			'VT:string' => function () {
				$normalizer = $this->createMock( DataValueNormalizer::class );
				$normalizer->method( 'normalize' )
					->willReturnCallback( static function ( DataValue $value ) {
						if ( $value instanceof StringValue ) {
							return new StringValue( strtoupper( $value->getValue() ) );
						} else {
							return $value;
						}
					} );
				return $normalizer;
			},
		] );
	}

	public function getMockReferenceNormalizer(): ReferenceNormalizer {
		return new ReferenceNormalizer( $this->getMockSnakNormalizer() );
	}

	public function getMockStatementNormalizer(): StatementNormalizer {
		return new StatementNormalizer( $this->getMockSnakNormalizer(), $this->getMockReferenceNormalizer() );
	}

}
