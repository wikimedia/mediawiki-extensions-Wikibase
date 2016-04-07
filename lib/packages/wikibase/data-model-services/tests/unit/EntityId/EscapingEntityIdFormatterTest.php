<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EscapingEntityIdFormatter;

/**
 * @covers Wikibase\DataModel\Services\EntityId\EscapingEntityIdFormatter
 *
 * @license GPL-2.0+
 * @author Daniel Kinzler
 */
class EscapingEntityIdFormatterTest extends PHPUnit_Framework_TestCase {

	public function testFormat() {
		$entityIdFormatter = $this->getMock( 'Wikibase\DataModel\Services\EntityId\EntityIdFormatter' );
		$entityIdFormatter->expects( $this->once() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( 'Q1 is &%$;§ > Q2' ) );

		$formatter = new EscapingEntityIdFormatter( $entityIdFormatter, 'htmlspecialchars' );
		$value = new ItemId( 'Q1' );

		$this->assertEquals( 'Q1 is &amp;%$;§ &gt; Q2', $formatter->formatEntityId( $value ) );
	}

}
