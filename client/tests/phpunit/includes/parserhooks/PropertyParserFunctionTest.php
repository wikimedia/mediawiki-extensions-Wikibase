<?php

namespace Wikibase\Test;

use DataValues\StringValue;
use Wikibase\Claim;
use Wikibase\Client\WikibaseClient;
use Wikibase\EntityId;
use Wikibase\Item;
use Wikibase\Lib\EntityRetrievingDataTypeLookup;
use Wikibase\Lib\SnakFormatter;
use Wikibase\Lib\TypedValueFormatter;
use Wikibase\ParserErrorMessageFormatter;
use Wikibase\Property;
use Wikibase\PropertyParserFunction;
use Wikibase\PropertySQLLookup;
use Wikibase\PropertyValueSnak;

/**
 * Tests for the Wikibase\PropertyParserFunction class.
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
 * @ingroup WikibaseClient
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseClient
 * @group PropertyParserFunctionTest
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyParserFunctionTest extends \PHPUnit_Framework_TestCase {

	private function getDefaultInstance() {
		$wikibaseClient = WikibaseClient::getDefaultInstance();

		$targetLanguage = \Language::factory( 'en' );
		$errorFormatter = new ParserErrorMessageFormatter( $targetLanguage );
		$dataTypeFactory = $wikibaseClient->getDataTypeFactory();
		$mockRepo = $this->newMockRepository();
		$mockResolver = new MockPropertyLabelResolver( $targetLanguage->getCode(), $mockRepo );

		$formatter = new SnakFormatter(
			new EntityRetrievingDataTypeLookup( $mockRepo ),
			new TypedValueFormatter(),
			$dataTypeFactory
		);

		return new PropertyParserFunction(
			$targetLanguage,
			$mockRepo,
			$mockResolver,
			$errorFormatter,
			$formatter
		);
	}

	private function newMockRepository() {
		$propertyId = new EntityId( Property::ENTITY_TYPE, 1337 );

		$entityLookup = new MockRepository();

		$item = Item::newEmpty();
		$item->setId( 42 );
		$item->addClaim( new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'Please write tests before merging your code' )
		) ) );
		$item->addClaim( new Claim( new PropertyValueSnak(
			$propertyId,
			new StringValue( 'or kittens will die' )
		) ) );

		$property = Property::newEmpty();
		$property->setId( $propertyId );

		$property->setDataTypeId( 'string' );
		$property->setLabel( 'en', 'kitten' );

		$entityLookup->putEntity( $item );
		$entityLookup->putEntity( $property );

		return $entityLookup;
	}

	public static function provideRenderForEntityId() {
		return array(
			array(
				'p1337',
				'Please write tests before merging your code, or kittens will die',
				'Congratulations, you just killed a kitten'
			),
			array(
				'kitten',
				'Please write tests before merging your code, or kittens will die',
				'Congratulations, you just killed a kitten'
			),
		);
	}

	/**
	 * @dataProvider provideRenderForEntityId
	 */
	public function testRenderForEntityId( $name, $expected, $info ) {
		$parserFunction = $this->getDefaultInstance();

		$status = $parserFunction->renderForEntityId(
			new EntityId( Item::ENTITY_TYPE, 42 ),
			$name
		);

		$this->assertTrue( $status->isOK() );

		$text = $status->getValue();
		$this->assertInternalType( 'string', $text );

		$this->assertEquals(
			$expected,
			$text,
			$info
		);
	}

}
