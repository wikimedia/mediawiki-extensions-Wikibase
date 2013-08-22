<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use Parser;
use Wikibase\Client\WikibaseClient;
use Wikibase\EntityLookup;
use Wikibase\Item;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\DataFieldValidator;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\EntityExistsValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\TypeValidator;

/**
 * Defines the data types supported by Wikibase.
 *
 * @since 0.4
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class WikibaseDataTypeBuilders {

	/**
	 * @var EntityLookup
	 */
	protected $entityLookup;

	/**
	 * @var EntityIdParser
	 */
	protected $entityIdParser;

	public function __construct(
		EntityLookup $lookup,
		EntityIdParser $idParser
	) {
		$this->entityIdParser = $idParser;
		$this->entityLookup = $lookup;
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

		$types = array(
			'wikibase-item' => array( $this, 'buildItemType' ),
			'commonsMedia' => array( $this, 'buildMediaType' ),
			'string' => array( $this, 'buildStringType' ),
			'time' => array( $this, 'buildTimeType' ),
			'globe-coordinate' => array( $this, 'buildCoordinateType' ),
			'url' => array( $this, 'buildUrlType' )
		);

		$experimental = array(
			// 'quantity'=> array( $this, 'buildQuantityType' ),
			// 'monolingual-text' => array( $this, 'buildMonolingualTextType' ),
			// 'multilingual-text' => array( $this, 'buildMultilingualTextType' ),
		);

		if ( defined( 'WB_EXPERIMENTAL_FEATURES' ) && WB_EXPERIMENTAL_FEATURES ) {
			$types = array_merge( $types, $experimental );
		}

		return $types;
	}

	public function buildItemType( $id ) {
		$validators = array();

		//NOTE: The DataValue in question is going to be an instance of EntityId!
		$validators[] = new TypeValidator( 'Wikibase\DataModel\Entity\EntityIdValue' );
		$validators[] = new EntityExistsValidator( $this->entityLookup );

		return new DataType( $id, 'wikibase-entityid', array(), array(), $validators );
	}

	public function buildMediaType( $id ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, 240 ); // Note: 240 is hardcoded in UploadBase
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.
		$validators[] = new RegexValidator( '@[#/:\\\\]@u', true ); // no nasty chars
		$validators[] = new RegexValidator( '@\..+@u', false ); // must contain a file extension
		//TODO: add a validator that checks the rules that MediaWiki imposes on filenames for uploads.
		//      $wgLegalTitleChars and $wgIllegalFileChars define this, but we need these for the *target* wiki.
		//TODO: add a validator that uses a foreign DB query to check whether the file actually exists on commons.

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array(), array(), array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	public function buildStringType( $id ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, 400, 'mb_strlen' );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array(), array(), array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	public function buildTimeType( $id ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// calendar model field
		$calendarIdValidators = array();
		$calendarIdValidators[] = new TypeValidator( 'string' );
		$calendarIdValidators[] = new StringLengthValidator( 1, 255 );
		$calendarIdValidators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.
		//TODO: enforce IRI/URI syntax / item URIs
		//TODO: enforce well known calendar models from config

		$validators[] = new DataFieldValidator( 'calendarmodel', // Note: validate the 'calendarmodel' field
			new CompositeValidator( $calendarIdValidators, true ) //Note: each validator is fatal
		);

		// time string field
		$timeStringValidators = array();
		$timeStringValidators[] = new TypeValidator( 'string' );

		$isoDataPattern = '!^[-+]\d{1,16}-(0\d|1[012])-([012]\d|3[01])T([01]\d|2[0123]):[0-5]\d:([0-5]\d|6[012])Z$!';
		$timeStringValidators[] = new RegexValidator( $isoDataPattern );

		$validators[] = new DataFieldValidator( 'time', // Note: validate the 'calendarmodel' field
			new CompositeValidator( $timeStringValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'time', array(), array(), array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	public function buildCoordinateType( $id ) {
		$validators = array();
		$validators[] = new TypeValidator( 'array' );

		// calendar model field
		$globeIdValidators = array();
		$globeIdValidators[] = new TypeValidator( 'string' );
		$globeIdValidators[] = new StringLengthValidator( 1, 255 );
		$globeIdValidators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.
		//TODO: enforce IRI/URI syntax / item URIs
		//TODO: enforce well known calendar models from config

		$validators[] = new DataFieldValidator( 'globe', // Note: validate the 'calendarmodel' field
			new CompositeValidator( $globeIdValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'globecoordinate', array(), array(), array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

	public function buildUrlType( $id ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, 500 );
		//TODO: validate UTF8 (here and elsewhere)

		$protocols = wfUrlProtocolsWithoutProtRel();
		$urlPattern = '#^' . $protocols .':(' . Parser::EXT_LINK_URL_CLASS . ')+#';

		//TODO: custom messages would be nice for RegexValidator
		$validators[] = new RegexValidator( $urlPattern );

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array(), array(), array( new TypeValidator( 'DataValues\DataValue' ), $topValidator ) );
	}

}
