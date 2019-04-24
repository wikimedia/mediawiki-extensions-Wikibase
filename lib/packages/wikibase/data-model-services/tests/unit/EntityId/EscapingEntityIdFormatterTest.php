<?php

namespace Wikibase\DataModel\Services\Tests\EntityId;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\EntityId\EntityIdFormatter;
use Wikibase\DataModel\Services\EntityId\EscapingEntityIdFormatter;

/**
 * @covers \Wikibase\DataModel\Services\EntityId\EscapingEntityIdFormatter
 *
 * @license GPL-2.0-or-later
 * @author Daniel Kinzler
 */
class EscapingEntityIdFormatterTest extends TestCase {

	public function testFormat() {
		$entityIdFormatter = $this->createMock( EntityIdFormatter::class );
		$entityIdFormatter->expects( $this->once() )
			->method( 'formatEntityId' )
			->will( $this->returnValue( 'Q1 is &%$;§ > Q2' ) );

		$formatter = new EscapingEntityIdFormatter( $entityIdFormatter, 'htmlspecialchars' );
		$value = new ItemId( 'Q1' );

		$this->assertEquals( 'Q1 is &amp;%$;§ &gt; Q2', $formatter->formatEntityId( $value ) );
	}

}
