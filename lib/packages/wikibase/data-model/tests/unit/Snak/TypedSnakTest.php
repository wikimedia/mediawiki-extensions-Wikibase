<?php

namespace Wikibase\DataModel\Tests\Snak;

use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\TypedSnak;

/**
 * @covers \Wikibase\DataModel\Snak\TypedSnak
 *
 * @group Wikibase
 * @group WikibaseDataModel
 * @group WikibaseSnak
 *
 * @license GPL-2.0-or-later
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnakTest extends \PHPUnit\Framework\TestCase {

	public function testGettersReturnCorrectValues() {
		/** @var Snak $snak */
		$snak = $this->createMock( Snak::class );
		$dataTypeId = 'awesome';

		$typedSnak = new TypedSnak( $snak, $dataTypeId );

		$this->assertEquals( $snak, $typedSnak->getSnak() );
		$this->assertSame( $dataTypeId, $typedSnak->getDataTypeId() );
	}

}
