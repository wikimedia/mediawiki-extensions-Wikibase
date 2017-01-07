<?php

namespace Wikibase\Repo\Tests\Search\Elastic\Fields;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\Item;
use Wikibase\DataModel\Entity\Property;
use Wikibase\DataModel\Snak\PropertyNoValueSnak;
use Wikibase\Repo\Search\Elastic\Fields\StatementCountField;

/**
 * @covers Wikibase\Repo\Search\Elastic\Fields\StatementCountField
 *
 * @group WikibaseElastic
 * @group Wikibase
 *
 * @license GPL-2.0+
 * @author Katie Filbert < aude.wiki@gmail.com >
 */
class StatementCountFieldTest extends PHPUnit_Framework_TestCase {

	public function testGetMapping() {
		$statementCountField = new StatementCountField();

		$expected = array(
			'type' => 'integer'
		);

		$this->assertSame( $expected, $statementCountField->getMapping() );
	}

	/**
	 * @dataProvider getFieldDataProvider
	 */
	public function testGetFieldData( $expected, EntityDocument $entity ) {
		$statementCountField = new StatementCountField();

		$this->assertSame( $expected, $statementCountField->getFieldData( $entity ) );
	}

	public function getFieldDataProvider() {
		$item = new Item();
		$item->getStatements()->addNewStatement( new PropertyNoValueSnak( 1 ) );

		return array(
			array( 1, $item ),
			array( 0, Property::newFromType( 'string' ) )
		);
	}

}
