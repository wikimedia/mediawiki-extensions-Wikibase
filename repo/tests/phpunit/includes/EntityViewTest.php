<?php

namespace Wikibase\Test;
use Wikibase\ItemContent;
use \ValueFormatters\ValueFormatterFactory;

/**
 * @covers Wikibase\EntityView
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA
 *
 * @file
 * @since 0.4
 *
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseRepo
 * @group EntityView
 *
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */
class EntityViewTest extends \PHPUnit_Framework_TestCase {

	/**
	 * @return array
	 */
	public function getHtmlForClaimsProvider() {
		$argLists = array();

		$itemContent = ItemContent::newEmpty();
		$itemContent->getEntity()->addClaim(
			new \Wikibase\Claim(
				new \Wikibase\PropertyNoValueSnak(
					new \Wikibase\EntityId( \Wikibase\Property::ENTITY_TYPE, 24 )
				)
			)
		);

		$argLists[] = array( $itemContent );

		return $argLists;
	}

	/**
	 * @dataProvider getHtmlForClaimsProvider
	 *
	 * @param \Wikibase\EntityContent $entityContent
	 */
	public function testGetHtmlForClaims( \Wikibase\EntityContent $entityContent ) {

		$entityView = \Wikibase\EntityView::newForEntityContent(
			$entityContent,
			new ValueFormatterFactory( $GLOBALS['wgValueFormatters'] )
		);

		// Using a DOM document to parse HTML output:
		$doc = new \DOMDocument();

		// Disable default error handling in order to catch warnings caused by malformed markup:
		libxml_use_internal_errors( true );

		// Try loading the HTML:
		$this->assertTrue( $doc->loadHTML( $entityView->getHtmlForClaims( $entityContent ) ) );

		// Check if no warnings have been thrown:
		$errorString = '';
		foreach( libxml_get_errors() as $error ) {
			$errorString .= "\r\n" . $error->message;
		}

		$this->assertEmpty( $errorString, 'Malformed markup:' . $errorString );

		// Clear error cache and re-enable default error handling:
		libxml_clear_errors();
		libxml_use_internal_errors();
	}

}
