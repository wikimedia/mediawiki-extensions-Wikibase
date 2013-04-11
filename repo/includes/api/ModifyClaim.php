<?php
namespace Wikibase\Api;

use ApiBase, MWException;
use Wikibase\Claims;
use Wikibase\PropertyValueSnak;
use Wikibase\Snak;
use Wikibase\Summary;

/**
 * Base class for modifying claims, with common functionality
 * for creating summaries.
 *
 * @todo decide if this is really needed or not
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
 * @ingroup API
 *
 * @licence GNU GPL v2+
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
abstract class ModifyClaim extends ApiWikibase {

	/**
	 * Create a summary
	 *
	 * @since 0.4
	 *
	 * @param string $action
	 *
	 * @return Summary
	 */
	protected function createSummary( $action ) {
		if ( !is_string( $action ) ) {
			throw new \MWException( 'action is invalid or unknown type.' );
		}

		$summary = new Summary( $this->getModuleName() );
		$summary->setAction( $action );

		return $summary;
	}

}
