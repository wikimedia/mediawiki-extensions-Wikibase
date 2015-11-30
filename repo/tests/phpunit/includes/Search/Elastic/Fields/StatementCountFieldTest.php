<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertyValueSnak;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\StatementCountField
 *
 * @group WikibaseElastic
 * @group WikibaseRepo
 * @group Wikibase
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementCountFieldTest extends \PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$statementCountField = new StatementCountField();

		$expected = array(
			'type' => 'integer'
		);

		$this->assertSame( $expected, $statementCountField->getMapping() );
	}

	public function testGetFieldData() {
		$statementCountField = new StatementCountField();

		$statements = new StatementList();
		$statements->addNewStatement(
			new PropertyValueSnak( new PropertyId( 'P1' ), new StringValue( 'o_O' ) )
		);

		$item = new Item();
		$item->setStatements( $statements );

		$this->assertSame( 1, $statementCountField->getFieldData( $item ) );
	}

}
