<?php

namespace Wikibase;

use InvalidArgumentException;
use Site;
use Wikibase\DataModel\SimpleSiteLink;

/**
 * Class for sitelink change operation
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
 * @since 0.4
 *
 * @ingroup WikibaseRepo
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class ChangeOpSiteLink implements ChangeOp {

	/**
	 * @since 0.4
	 *
	 * @var string
	 */
	protected $siteId;

	/**
	 * @since 0.4
	 *
	 * @var string|null
	 */
	protected $linkPage;

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 * @param string|null $linkPage
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $linkPage ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $linkPage ) && $linkPage !==null ) {
			throw new InvalidArgumentException( '$linkPage needs to be a string|null' );
		}

		$this->siteId = $siteId;
		$this->linkPage = $linkPage;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 *
	 * @throws InvalidArgumentException
	 */
	public function apply( Entity $entity ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpSiteLink can only be applied to Item instances' );
		}

		$entity->addSimpleSiteLink( new SimpleSiteLink( $this->siteId, $this->linkPage ) );
	}

}
