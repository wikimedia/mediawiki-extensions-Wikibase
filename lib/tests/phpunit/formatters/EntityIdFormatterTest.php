<?php

namespace Wikibase\Test;

use ValueFormatters\FormatterOptions;
use Wikibase\EntityId;
use Wikibase\Lib\EntityIdFormatter;
use Wikibase\Lib\EntityIdLabelFormatter;
use Wikibase\Item;
use Wikibase\Property;

/**
 * Unit tests for the Wikibase\Lib\EntityIdFormatter class.
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
 * @file
 * @since 0.4
 *
 * @ingroup WikibaseLibTest
 *
 * @group ValueFormatters
 * @group DataValueExtensions
 * @group WikibaseLib
 * @group EntityIdFormatterTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class EntityIdFormatterTest extends \PHPUnit_Framework_TestCase {

	protected function newEntityIdFormatter() {
		$options = new FormatterOptions();
		$options->setOption(
			EntityIdFormatter::OPT_PREFIX_MAP,
			array(
				 Item::ENTITY_TYPE => 'I',
				 Property::ENTITY_TYPE => 'PropERTY',
			)
		);

		return new EntityIdFormatter( $options );
	}

	/**
	 * @since 0.4
	 *
	 * @return array
	 */
	public function validProvider() {
		$argLists = array();

		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 42 ), 'I42' );
		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 9001 ), 'I9001' );
		$argLists[] = array( new EntityId( Item::ENTITY_TYPE, 1 ), 'I1' );

		$argLists[] = array( new EntityId( Property::ENTITY_TYPE, 42 ), 'PropERTY42' );
		$argLists[] = array( new EntityId( Property::ENTITY_TYPE, 9001 ), 'PropERTY9001' );
		$argLists[] = array( new EntityId( Property::ENTITY_TYPE, 1 ), 'PropERTY1' );

		return $argLists;
	}

	/**
	 * @dataProvider validProvider
	 *
	 * @param EntityId $entityId
	 * @param string $expectedString
	 */
	public function testParseWithValidArguments( EntityId $entityId, $expectedString ) {
		$formatter = $this->newEntityIdFormatter();

		$formattingResult = $formatter->format( $entityId );

		$this->assertInternalType( 'string', $formattingResult );
		$this->assertEquals( $expectedString, $formattingResult );
	}

}
