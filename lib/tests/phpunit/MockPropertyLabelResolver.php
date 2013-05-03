<?php

namespace Wikibase\Test;
use Wikibase\Claims;
use Wikibase\Entity;
use Wikibase\EntityId;
use Wikibase\EntityLookup;
use Wikibase\Item;
use Wikibase\PropertyLabelResolver;
use Wikibase\SiteLink;
use Wikibase\SiteLinkLookup;
use Wikibase\Property;
use string;

/**
 * Mock resolver, based on a MockRepository
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
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 */
class MockPropertyLabelResolver implements PropertyLabelResolver {

	protected $repo;

	protected $lang;

	/**
	 * @param string  $lang
	 * @param MockRepository $repo
	 */
	public function __construct( $lang, MockRepository $repo ) {
		$this->lang = $lang;
		$this->repo = $repo;
	}

	/**
	 * @param string[] $labels  the labels
	 * @param string   $recache ignored
	 *
	 * @return EntityId[] a map of strings from $labels to the corresponding entity ID.
	 */
	public function getPropertyIdsForLabels( array $labels, $recache = '' ) {
		$ids = array();

		foreach ( $labels as $label ) {
			$prop = $this->repo->getPropertyByLabel( $label, $this->lang );

			$ids[$label] = $prop->getId();
		}

		return $ids;
	}
}
