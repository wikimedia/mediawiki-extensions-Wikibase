<?php

namespace Wikibase\Lib;

use DataTypes\DataType;
use Wikibase\Client\WikibaseClient;
use Wikibase\EntityLookup;
use Wikibase\Item;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\Validators\CompositeValidator;
use Wikibase\Validators\DataFieldValidator;
use Wikibase\Validators\DataValueValidator;
use Wikibase\Validators\EntityExistsValidator;
use Wikibase\Validators\EntityIdValidator;
use Wikibase\Validators\RegexValidator;
use Wikibase\Validators\StringLengthValidator;
use Wikibase\Validators\TypeValidator;

/**
 * Defines the data types supported by Wikibase.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
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

	public function __construct() {
		//TODO: Take a service registry as a parameter. That's OK for a builder class.
		//      But for that, the client and repo need a common interface for their service registries.
		//      Note that callers should not need knowledge about which services are needed here.
		//      For now, we fake it using global state:

		if ( defined( 'WB_VERSION' ) ) { // repo mode
			$repo = WikibaseRepo::getDefaultInstance();
			$this->entityIdParser = $repo->getEntityIdParser();
			$this->entityLookup = $repo->getEntityLookup();
		} elseif ( defined( 'WBC_VERSION' ) ) { // client mode
			$client = WikibaseClient::getDefaultInstance();
			$this->entityIdParser = $client->getEntityIdParser();
			$this->entityLookup = $client->getStore()->getEntityLookup();
		} else {
			throw new \RuntimeException( "Neither repo nor client found!" );
		}
	}

	/**
	 * @return array DataType builder specs
	 */
	public function getDataTypeBuilders() {
		return array(
			'wikibase-item' => array( $this, 'buildItemType' ),
			'commonsMedia' => array( $this, 'buildMediaType' ),
			'string' => array( $this, 'buildStringType' ),
			'time' => array( $this, 'buildTimeType' ),
			'globe-coordinate' => array( $this, 'buildCoordinateType' ),
		);
	}

	public function buildItemType( $id ) {
		$validators = array();

		//NOTE: The DataValue in question is going to be an instance of EntityId!
		$validators[] = new TypeValidator( 'Wikibase\EntityId' );
		$validators[] = new EntityExistsValidator( $this->entityLookup );

		return new DataType( $id, 'wikibase-entityid', array(), array(), $validators );
	}

	public function buildMediaType( $id ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, 255 );
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.
		//TODO: add a validator that checks the rules that MediaWiki imposes on filenames for uploads.
		//TODO: add a validator that uses a foreign DB query to check whether the file actually exists on commons.

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array(), array(), array( $topValidator ) );
	}

	public function buildStringType( $id ) {
		$validators = array();

		$validators[] = new TypeValidator( 'string' );
		$validators[] = new StringLengthValidator( 1, 255, 'mb_strlen' ); //XXX: restrict what exactly?
		$validators[] = new RegexValidator( '/^\s|[\r\n\t]|\s$/', true ); // no leading/trailing whitespace, no line breaks.

		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'string', array(), array(), array( $topValidator ) );
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
		$timeStringValidators[] = new RegexValidator( $isoDataPattern, true );

		$validators[] = new DataFieldValidator( 'time', // Note: validate the 'calendarmodel' field
			new CompositeValidator( $timeStringValidators, true ) //Note: each validator is fatal
		);

		// top validator
		$topValidator = new DataValueValidator( //Note: validate the DataValue's native value.
			new CompositeValidator( $validators, true ) //Note: each validator is fatal
		);

		return new DataType( $id, 'time', array(), array(), array( $topValidator ) );
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

		return new DataType( $id, 'globecoordinate', array(), array(), array( $topValidator ) );
	}

}
