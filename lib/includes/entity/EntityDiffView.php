<?php

namespace Wikibase;
use IContextSource;
use Html;
use Diff\IDiff;
use Diff\DiffOp;

/**
 * Class for generating views of EntityDiff objects.
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
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */

class EntityDiffView extends DiffView {

	/**
	 * Returns a new EntityDiffView for the provided EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $diff
	 * @param IContextSource $contextSource
	 *
	 * @return EntityDiffView
	 */
	public static function newForDiff( EntityDiff $diff, IContextSource $contextSource = null, $entityLookup = null ) {
		return new static( array(), $diff, $contextSource, $entityLookup );
		// TODO: grep for new EntityDiffView and rep by this
	}

	/**
	 * Get HTML for a changed snak
	 *
	 * @since 0.4
	 *
	 * @param Claim $claim
	 *
	 * @return string
	 */
	protected function getChangedSnakHtml( Claim $claim ) {
		$snakType = $claim->getMainSnak()->getType();
		$diffValueString = $snakType;

		if ( $snakType === 'value' ) {
			$dataValue = $claim->getMainSnak()->getDataValue();

			//FIXME: This will break for types other than EntityId or StringValue
			//we do not have a generic way to get string representations of the values yet
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else {
				$diffValueString = $dataValue->getValue();
			}
		}

		return $diffValueString;
	}
}