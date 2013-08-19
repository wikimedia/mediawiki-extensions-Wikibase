<?php

namespace Wikibase\Test\Api;
use Wikibase\Claim;
use Wikibase\EntityId;
use Wikibase\Property;
use Wikibase\PropertyContent;

/**
 * Unit tests for the Wikibase\Repo\Api\MergeItems class.
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
 * @ingroup WikibaseRepoTest
 *
 * @group API
 * @group Database
 * @group Wikibase
 * @group WikibaseAPI
 * @group WikibaseRepo
 * @group MergeItemsTest
 *
 * @group medium
 *
 * @licence GNU GPL v2+
 * @author Adam Shorland
 */
class MergeItemsTest extends WikibaseApiTestCase {

	protected function entityProvider() {
		static $isSetup = false;

		if ( !$isSetup ) {
			$prop42 = new EntityId( Property::ENTITY_TYPE, 42 );
			$prop = PropertyContent::newEmpty();
			$prop->getEntity()->setId( $prop42 );
			$prop->getEntity()->setDataTypeId( 'string' );
			$prop->save( 'testing' );

			$isSetup = true;
		}
	}

	function makeMergeRequest(){

	}

}
