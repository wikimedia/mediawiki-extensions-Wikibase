<?php

namespace Wikibase\Test;
use Wikibase\PropertyContent, Wikibase\EntityContent;

/**
 * Tests for the Wikibase\PropertyContent class.
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
 * @since 0.1
 *
 * @ingroup WikibaseRepoTest
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseProperty
 * @group WikibaseRepo
 * @group WikibaseContent
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class PropertyContentTest extends EntityContentTest {

	/**
	 * @see EntityContentTest::getContentClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\PropertyContent';
	}


	/**
	 * @see EntityContentTest::newEmpty
	 *
	 * @since 0.2
	 *
	 * @return EntityContent
	 */
	protected function newEmpty() {
		$content = PropertyContent::newEmpty();

		$dataTypes = \Wikibase\Settings::get( 'dataTypes' );
		$content->getProperty()->setDataTypeById( array_shift( $dataTypes ) );

		return $content;
	}

	public function testLabelUniquenessRestriction() {
		\Wikibase\StoreFactory::getStore()->newTermCache()->clear();

		$propertyContent = PropertyContent::newEmpty();
		$propertyContent->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setLabel( 'de', 'testLabelUniquenessRestriction' );
		$propertyContent->getProperty()->setDataTypeById( 'wikibase-item' );

		$status = $propertyContent->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1 = PropertyContent::newEmpty();
		$propertyContent1->getProperty()->setLabel( 'nl', 'testLabelUniquenessRestriction' );
		$propertyContent1->getProperty()->setDataTypeById( 'wikibase-item' );

		$status = $propertyContent1->save( 'create property', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "property creation should work" );

		$propertyContent1->getProperty()->setLabel( 'en', 'testLabelUniquenessRestriction' );

		$status = $propertyContent1->save( 'save property' );
		$this->assertFalse( $status->isOK(), "saving a property with duplicate label+lang should not work" );
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-not-unique-wikibase-property' ) );
	}

}
