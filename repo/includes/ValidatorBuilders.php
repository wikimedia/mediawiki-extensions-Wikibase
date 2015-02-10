<?php

namespace Wikibase\Repo;

use DataValues\TimeValue;
use ValueValidators\ValueValidator;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\Lib\Store\EntityLookup;
use Wikibase\Utils;
use Wikibase\Validators\AlternativeValidator;
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
 * Defines validators for the data types supported by Wikibase.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ValidatorBuilders {

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
	 * @return callable[] ValueValidator for DataTypes build spec
	 */
	public function getDataTypeValidators() {

		$types = array(
			'commonsMedia'      => array( $this, 'buildMediaValidators' ),
			'globe-coordinate'  => array( $this, 'buildCoordinateValidators' ),
			'quantity'          => array( $this, 'buildQuantityValidators' ),
			'string'            => array( $this, 'buildStringValidators' ),
			'time'              => array( $this, 'buildTimeValidators' ),
			'url'               => array( $this, 'buildUrlValidators' ),
			'wikibase-item'     => array( $this, 'buildItemValidators' ),
			'wikibase-property' => array( $this, 'buildPropertyValidators' ),
			'monolingualtext'   => array( $this, 'buildMonolingualTextValidators' ),
		);

		$experimental = array(
			// 'multilingualtext' => array( $this, 'buildMultilingualTextValidators' ),
		);

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$types = array_merge( $types, $experimental );
		}

		return $types;
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildItemValidators() {
		$validators = array();

		//NOTE: The DataValue in question is going to be an instance of EntityId!
		$validators[] = new TypeValidator( 'Wikibase\DataModel\Entity\EntityIdValue' );
		$validators[] = new EntityExistsValidator( $this->entityLookup, Item::ENTITY_TYPE );

		return $validators;
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildPropertyValidators( ) {
		$validators = array();

		//NOTE: The DataValue in question is going to be an instance of EntityId!
		$validators[] = new TypeValidator( 'Wikibase\DataModel\Entity\EntityIdValue' );
		$validators[] = new EntityExistsValidator( $this->entityLookup, Property::ENTITY_TYPE );

		return $validators;
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
	 * @return ValueValidator[]
	 */
	public function buildMediaValidators() {
		// oi_archive_name is max 255 bytes, which include a timestamp and an exclamation mark,
		// so restrict file name to 240 bytes (see UploadBase::getTitle).
		$validators = $this->getCommonStringValidators( 240 );

		$validators[] = new RegexValidator( '@[#/:\\\\]@u', true ); // no nasty chars
		// Must contain a non-empty file name and a non-empty, character-only file extension.
		$validators[] = new RegexValidator( '/.\.\w+$/u' );
		//TODO: add a validator that checks the rules that MediaWiki imposes on filenames for uploads.
		//      $wgLegalTitleChars and $wgIllegalFileChars define this, but we need these for the *target* wiki.
		//TODO: add a validator that uses a foreign DB query to check whether the file actually exists on commons.

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildStringValidators() {
		$validators = $this->getCommonStringValidators();

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildMonolingualTextValidators() {
		$validators = array();

		$validators[] = new DataFieldValidator(
			'text',
			new CompositeValidator( $this->getCommonStringValidators() ) //Note: each validator is fatal
		);

		$validators[] = new DataFieldValidator(
			'language',
			new MembershipValidator( Utils::getLanguageCodes() )
		);

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildTimeValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// Expected to be a short IRI, see TimeFormatter and TimeParser.
		$urlValidator = $this->getUrlValidator( array( 'http', 'https' ), 255 );
		//TODO: enforce well known calendar models from config

		$validators[] = new DataFieldValidator( 'calendarmodel', $urlValidator );

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

		$validators[] = new DataFieldValidator(
			'time',
			new CompositeValidator( $timeStringValidators ) //Note: each validator is fatal
		);

		$precisionValidators = array();
		$precisionValidators[] = new TypeValidator( 'integer' );
		$precisionValidators[] = new NumberRangeValidator( TimeValue::PRECISION_Ga, $maxPrecision );

		$validators[] = new DataFieldValidator(
			'precision',
			new CompositeValidator( $precisionValidators ) //Note: each validator is fatal
		);

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildCoordinateValidators() {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// Expected to be a short IRI, see GlobeCoordinateValue and GlobeCoordinateParser.
		$urlValidator = $this->getUrlValidator( array( 'http', 'https' ), 255 );
		//TODO: enforce well known reference globes from config

		$validators[] = new DataFieldValidator( 'precision', new NumberValidator() );

		$validators[] = new DataFieldValidator( 'globe', $urlValidator );

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @param string[] $urlSchemes List of URL schemes, e.g. 'http'
	 * @param int $maxLength Defaults to 500 characters. Even if URLs are unlimited in theory they
	 * should be limited to about 2000. About 500 is a reasonable compromise.
	 * @see http://stackoverflow.com/a/417184
	 *
	 * @return CompositeValidator
	 */
	private function getUrlValidator( $urlSchemes, $maxLength = 500 ) {
		$validators = array();
		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 2, $maxLength );

		$urlValidators = new UrlSchemeValidators();
		$urlSchemeValidators = $urlValidators->getValidators( $urlSchemes );
		$validators[] = new UrlValidator( $urlSchemeValidators );

		return new CompositeValidator( $validators ); //Note: each validator is fatal
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildUrlValidators( ) {
		$urlValidator = $this->getUrlValidator( $this->urlSchemes );

		$topValidator = new DataValueValidator(
			$urlValidator
		);

		return array( new TypeValidator( 'DataValues\DataValue' ), $topValidator );
	}

	/**
	 * @return ValueValidator[]
	 */
	public function buildQuantityValidators( ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// the 'amount' field is already validated by QuantityValue's constructor
		// the 'digits' field is already validated by QuantityValue's constructor

		$unitValidators = new AlternativeValidator( array(
			// NOTE: "1" is always considered legal for historical reasons,
			// since we use it to represent "unitless" quantities. We could also use
			// http://qudt.org/vocab/unit#Unitless or https://www.wikidata.org/entity/Q199
			new MembershipValidator( array( '1' ) ),
			$this->getUrlValidator( array( 'http', 'https' ), 255 ),
		) );
		$validators[] = new DataFieldValidator( 'unit', $unitValidators );

		$topValidator = new DataValueValidator(
			new CompositeValidator( $validators ) //Note: each validator is fatal
		);

		return array( new TypeValidator( 'DataValues\QuantityValue' ), $topValidator );
	}

}
