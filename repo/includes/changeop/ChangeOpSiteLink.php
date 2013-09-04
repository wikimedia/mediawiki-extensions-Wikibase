<?php

namespace Wikibase;

use InvalidArgumentException;
use Site;
use Wikibase\DataModel\SimpleSiteLink;
use Wikibase\DataModel\Entity\ItemId;

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
 * @author Michał Łazowik
 */
class ChangeOpSiteLink extends ChangeOp {

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
	protected $pageName;

	/**
	 * @since 0.5
	 *
	 * @var string[]|null
	 */
	 protected $badges;

	/**
	 * @since 0.4
	 *
	 * @param string $siteId
	 * @param string|null $pageName Null in case the link with the provided siteId should be removed
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct( $siteId, $pageName, $badges = null ) {
		if ( !is_string( $siteId ) ) {
			throw new InvalidArgumentException( '$siteId needs to be a string' );
		}

		if ( !is_string( $pageName ) && $pageName !== null ) {
			throw new InvalidArgumentException( '$linkPage needs to be a string|null' );
		}

		if ( !is_array( $badges ) && $badges !== null ) {
			throw new InvalidArgumentException( '$badges need to be an array|null' );
		}

		$this->siteId = $siteId;
		$this->pageName = $pageName;
		$this->badges = $badges;
	}

	/**
	 * Applies the change to the given entity
	 *
	 * @since 0.4
	 *
	 * @param Entity $entity
	 * @param Summary|null $summary
	 *
	 * @throws InvalidArgumentException
	 * @throws ChangeOpException
	 */
	public function apply( Entity $entity, Summary $summary = null ) {
		if ( !( $entity instanceof Item ) ) {
			throw new InvalidArgumentException( 'ChangeOpSiteLink can only be applied to Item instances' );
		}

		if ( $this->pageName === null ) {
			if ( $entity->hasLinkToSite( $this->siteId ) ) {
				$this->updateSummary( $summary, 'remove', $this->siteId, $entity->getSimpleSiteLink( $this->siteId )->getPageName() );
				$entity->removeSiteLink( $this->siteId );
			} else {
				//TODO: throw error, or ignore silently?
			}
		} else {
			if ( $this->badges === null ) {
				// If badges are not set make sure that they remain intact
				if ( $entity->hasLinkToSite( $this->siteId ) ) {
					$badges = $entity->getSimpleSiteLink( $this->siteId )->getBadges();
				} else {
					$badges = array();
				}
			} else {
				$badges = array();

				foreach ( $this->badges as $badgeSerialization ) {
					$badges[] = new ItemId( $badgeSerialization );
					//TODO: make sure that these Items actually exist
				}
			}

			$entity->hasLinkToSite( $this->siteId ) ? $action = 'set' : $action = 'add';
			$this->updateSummary( $summary, $action, $this->siteId, $this->pageName );
			$entity->addSimpleSiteLink( new SimpleSiteLink( $this->siteId, $this->pageName, $badges ) );
		}

		return true;
	}
}
