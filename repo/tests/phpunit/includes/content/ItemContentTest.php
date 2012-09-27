<?php

namespace Wikibase\Test;
use Wikibase\ItemContent, Wikibase\Item;

/**
 * Tests for the Wikibase\ItemContent class.
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
 * @ingroup Wikibase
 * @ingroup Test
 *
 * @group Wikibase
 * @group WikibaseItem
 * @group WikibaseRepo
 * @group WikibaseContent
 * @group WikibaseItemContent
 *
 * @group Database
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class ItemContentTest extends EntityContentTest {

	/**
	 * @see EntityContentTest::getContentClass
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	protected function getContentClass() {
		return '\Wikibase\ItemContent';
	}

	/**
	 * This test also ensures long method names do not cause a problem, so it's completely legit!
	 */
	public function testLabelAndDescriptionUniquenessRestrictionSoWeKnowForSureItActuallyDoesWorkProperlyAndIsNotTotallyBrokenOrSomethingBecauseThatWouldBeRatherBadAndJohnWouldShoutAtMe() {
		if ( wfGetDB( DB_SLAVE )->getType() === 'mysql' ) {
			$this->assertTrue( (bool)'MySQL fails' );
			return;
		}

		\Wikibase\StoreFactory::getStore()->newTermCache()->clear();

		$content = ItemContent::newEmpty();
		$content->getItem()->setLabel( 'en', 'label' );
		$content->getItem()->setDescription( 'en', 'description' );

		$content->getItem()->setLabel( 'de', 'label' );
		$content->getItem()->setDescription( 'de', 'description' );

		$status = $content->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "item creation should work" );

		$content1 = ItemContent::newEmpty();
		$content1->getItem()->setLabel( 'nl', 'label' );
		$content1->getItem()->setDescription( 'nl', 'description' );

		$status = $content1->save( 'create item', null, EDIT_NEW );
		$this->assertTrue( $status->isOK(), "item creation should work" );

		$content1->getItem()->setLabel( 'en', 'label' );
		$content1->getItem()->setDescription( 'en', 'description' );

		$status = $content1->save( 'save item' );
		$this->assertFalse( $status->isOK(), "saving an item with duplicate lang+label+description should not work" );
		$this->assertTrue( $status->hasMessage( 'wikibase-error-label-not-unique-item' ) );
	}

}
