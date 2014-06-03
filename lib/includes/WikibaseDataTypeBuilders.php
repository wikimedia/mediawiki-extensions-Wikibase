<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use DataValues\TimeValue;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Utils;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\DataFieldValidator;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\EntityExistsValidator;
use Wikibase\Validators\MembershipValidator;
use Wikibase\Validators\NumberRangeValidator;
use Wikibase\Validators\NumberValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\TypeValidator;
use Wikibase\Validators\UrlSchemeValidators;
use Wikibase\Validators\UrlValidator;

/**
 * Defines the data types supported by Wikibase.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseDataTypeBuilders {

	/**
	 * @var EntityIdParser
	 */
	private $entityIdParser;

	/**
	 * @var EntityLookup
	 */
	private $entityLookup;

	/**
	 * @var string[]
	 */
	private $urlSchemes;

	/**
	 * @param EntityLookup $lookup
	 * @param EntityIdParser $idParser
	 * @param string[] $urlSchemes
	 */
	public function __construct(
		EntityLookup $lookup,
		EntityIdParser $idParser,
		array $urlSchemes
	) {
		$this->entityIdParser = $idParser;
		$this->entityLookup = $lookup;
		$this->urlSchemes = $urlSchemes;
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getDataTypeBuilders() {
		//XXX: Using callbacks here is somewhat pointless, we could just as well have a
		//     registerTypes( DataTypeFactory ) method and register the DataType objects
		//     directly. But that would make it awkward to filter the types according to
		//     the dataTypes setting. On the other hand, perhaps that setting should only
		//     be used for the UI, and the factory should simply know all data types always.

		/**
		 * Data types to data value types mapping:
		 * commonsMedia     => string (camel case, FIXME maybe?)
		 * globe-coordinate => globecoordinate (FIXME!)
		 * monolingualtext  => monolingualtext
		 * multilingualtext => multilingualtext
		 * quantity         => quantity
		 * string           => string
		 * time             => time
		 * url              => string
		 * wikibase-item    => wikibase-entityid
		 */

		$types = array(
			'commonsMedia'     => array( $this, 'buildMediaType' ),
			'globe-coordinate' => array( $this, 'buildCoordinateType' ),
			'quantity'         => array( $this, 'buildQuantityType' ),
			'string'           => array( $this, 'buildStringType' ),
			'time'             => array( $this, 'buildTimeType' ),
			'url'              => array( $this, 'buildUrlType' ),
			'wikibase-item'    => array( $this, 'buildItemType' ),
		);

		$experimental = array(
			'monolingualtext' => array( $this, 'buildMonolingualTextType' ),
			// 'multilingualtext' => array( $this, 'buildMultilingualTextType' ),
		);

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$types = array_merge( $types, $experimental );
		}

		return $types;
	}

	/**
	 * @param string $id Data type ID, e.g. 'wikibase-item'
	 *
	 * @return DataType
	 */
	public function buildItemType( $id ) {
		$validators = array();

		//NOTE: The DataValue in question is going to be an instance of EntityId!
		$validators[] = new TypeValidator( 'Wikibase\DataModel\Entity\EntityIdValue' );
		$validators[] = new EntityExistsValidator( $this->entityLookup );

		return new DataType( $id, 'wikibase-entityid', $validators );
	}

	/**
	 * @param int $maxLength Defaults to 400 characters. This was an arbitrary decision when it
	 * turned out that 255 was to short for descriptions.
	 *
	 * @return ValueValidator[]
	 */
	private function getCommonStringValidators( $maxLength = 400  ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		//TODO: validate UTF8 (here and elsewhere)
		$validators[] = new StringLengthValidator( 1, $maxLength, 'mb_strlen' );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		return $validators;
	}

	/**
	 * @param string $id Data type ID, e.g. 'commonsMedia'
	 *
	 * @return DataType
	 */
	public function buildMediaType( $id ) {
		// oi_archive_name is max 255 bytes, which include a timestamp and an exclamation mark,
		// so restrict file name to 240 bytes (see UploadBase::getTitle).
		$validators = $this->getCommonStringValidators( 240 );

		$validators[] = new RegexValidator( '@[#/:\\\\]@u', true ); // no nasty chars
		$validators[] = new RegexValidator( '@\..+@u', false ); // must contain a file extension
		//TODO: add a validator that checks the rules that MediaWiki imposes on filenames for uploads.
		//      $wgLegalTitleChars and $wgIllegalFileChars define this, but we need these for the *target* wiki.
		//TODO: add a validator that uses a foreign DB query to check whether the file actually exists on commons.

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string $id Data type ID, e.g. 'string'
	 *
	 * @return DataType
	 */
	public function buildStringType( $id ) {
		$validators = $this->getCommonStringValidators();

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string $id Data type ID, e.g. 'monolingualtext'
	 *
	 * @return DataType
	 */
	public function buildMonolingualTextType( $id ) {
		$textValidator = new DataFieldValidator(
			'text',
			new CompositeValidator(
				$this->getCommonStringValidators(),
				true //Note: each validator is fatal
			)
		);

		$languageValidator = new DataFieldValidator(
			'language',
			new MembershipValidator( Utils::getLanguageCodes() )
		);

		$topValidator = new DataValueValidator( new CompositeValidator(
			array( $textValidator, $languageValidator ),
			true
		) );

		return new DataType( $id, 'monolingualtext', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string $id Data type ID, e.g. 'time'
	 *
	 * @return DataType
	 */
	public function buildTimeType( $id ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// calendar model field
		$calendarIdValidators = array();
		// Expected to be a short IRI, see TimeFormatter and TimeParser.
		$calendarIdValidators[] = $urlValidator = $this->buildUrlValidator( array( 'http', 'https' ), 255 );
		//TODO: enforce well known calendar models from config

		$validators[] = new DataFieldValidator( 'calendarmodel', // Note: validate the 'calendarmodel' field
			new CompositeValidator( $calendarIdValidators, true ) //Note: each validator is fatal
		);

		// time string field
		$timeStringValidators = array();
		$timeStringValidators[] = new TypeValidator( 'string' );

		// down to the second
		//$maxPrecision = TimeValue::PRECISION_SECOND;
		//$isoDataPattern = '!^[-+]\d{1,16}-(0\d|1[012])-([012]\d|3[01])T([01]\d|2[0123]):[0-5]\d:([0-5]\d|6[012])Z$!';

		// down to the day
		$maxPrecision = TimeValue::PRECISION_DAY;
		$isoDataPattern = '!^[-+]\d{1,16}-(0\d|1[012])-([012]\d|3[01])T00:00:00Z$!';

		$timeStringValidators[] = new RegexValidator( $isoDataPattern );

		$validators[] = new DataFieldValidator( 'time', // Note: validate the 'time' field
			new CompositeValidator( $timeStringValidators, true ) //Note: each validator is fatal
		);

		$precisionValidators = array();
		$precisionValidators[] = new TypeValidator( 'integer' );
		$precisionValidators[] = new NumberRangeValidator( TimeValue::PRECISION_Ga, $maxPrecision );

		$validators[] = new DataFieldValidator( 'precision', // Note: validate the 'precision' field
			new CompositeValidator( $precisionValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'time', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string $id Data type ID, typically 'globe-coordinate'
	 *
	 * @return DataType
	 */
	public function buildCoordinateType( $id ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		$globeIdValidators = array();
		// Expected to be a short IRI, see GlobeCoordinateValue and GlobeCoordinateParser.
		$globeIdValidators[] = $urlValidator = $this->buildUrlValidator( array( 'http', 'https' ), 255 );
		//TODO: enforce well known reference globes from config

		$precisionValidators = array();
		$precisionValidators[] = new NumberValidator();

		$validators[] = new DataFieldValidator( 'precision',
			new CompositeValidator( $precisionValidators, true )
		);

		$validators[] = new DataFieldValidator( 'globe', // Note: validate the 'globe' field
			new CompositeValidator( $globeIdValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'globecoordinate', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string[] $urlSchemes List of URL schemes, e.g. 'http'
	 * @param int $maxLength Defaults to 500 characters. Even if URLs are unlimited in theory they
	 * should be limited to about 2000. About 500 is a reasonable compromise.
	 * @see http://stackoverflow.com/a/417184
	 *
	 * @return CompositeValidator
	 */
	public function buildUrlValidator( $urlSchemes, $maxLength = 500 ) {
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 2, $maxLength );

		$urlValidators = new UrlSchemeValidators();
		$urlSchemeValidators = $urlValidators->getValidators( $urlSchemes );
		$validators[] = new UrlValidator( $urlSchemeValidators );

		return new CompositeValidator( $validators, true ); //Note: each validator is fatal
	}

	/**
	 * @param string $id Data type ID, typically 'url'
	 *
	 * @return DataType
	 */
	public function buildUrlType( $id ) {
		$urlValidator = $this->buildUrlValidator( $this->urlSchemes );

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			$urlValidator
		);

		return new DataType( $id, 'string', array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	/**
	 * @param string $id Data type ID, typically 'quantity'
	 *
	 * @return DataType
	 */
	public function buildQuantityType( $id ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// the 'amount' field is already validated by QuantityValue's constructor
		// the 'digits' field is already validated by QuantityValue's constructor

		// only allow the '1' unit for now:
		$unitValidators = array(
			new TypeValidator( 'string' ),
			new RegexValidator( '/^1$/', false, 'unknown-unit' ),
		);

		$validators[] = new DataFieldValidator( 'unit', // Note: validate the 'unit' field
			new CompositeValidator( $unitValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'quantity', array( new TypeValidator( 'DataValues\QuantityValue' ), $topValidator ) );
	}

}
