<?php

namespace Wikibase\Test;

use DataTypes\DataType;
use DataTypes\DataTypeFactory;
use DataValues\StringValue;
use Wikibase\DataModel\Claim\Claim;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Reference;
use Wikibase\DataModel\ReferenceList;
use Wikibase\Lib\InMemoryDataTypeLookup;
use Wikibase\Lib\PropertyDataTypeLookup;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Claim\Statement;
use Wikibase\Validators\SnakValidator;

/**
 * @covers Wikibase\Api\ApiErrorReporter
 *
 * @group Wikibase
 * @group WikibaseValidators
 * @group WikibaseAPI
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class ApiErrorReporterTest extends \PHPUnit_Framework_TestCase {

	public function testDieUsage() {
		$this->fail( 'test me, test more' );
	}

}

