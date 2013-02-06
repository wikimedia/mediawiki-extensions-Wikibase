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
	 * @since 0.4
	 *
	 * @var EntityLookup|null
	 */
	private static $entityLookup;

	/**
	 * Returns a new EntityDiffView for the provided EntityDiff.
	 *
	 * @since 0.4
	 *
	 * @param EntityDiff $diff
	 * @param IContextSource $contextSource
	 * @param EntityLookup|null $entityLookup
	 *
	 * @return EntityDiffView
	 */
	public static function newForDiff( EntityDiff $diff, IContextSource $contextSource = null, $entityLookup = null ) {
		self::$entityLookup = $entityLookup;
		return new static( array(), $diff, $contextSource, $entityLookup );
		// TODO: grep for new EntityDiffView and rep by this
	}

	/**
	 * Does the actual work.
	 *
	 * @since 0.4
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws \MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			if ( $path[0] === 'claim' ) {
				if ( $op->getType() === 'change' || $op->getType() === 'remove' ) {
					$propertyId = $op->getOldValue()->getPropertyId();
				} elseif ( $op->getType() === 'add' ) {
					$propertyId = $op->getNewValue()->getPropertyId();
				}
				$name = $path[0] . ' / ' . $this->getEntityLabel( $propertyId );
			} else {
				$name = implode( ' / ', $path ); // TODO: l10n
			}

			$html = $this->generateDiffHeaderHtml( $name );

			if ( $op->getType() === 'add' ) {
				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = $this->getClaimHtml( $newValue );
				}
				$html .= $this->generateAddOpHtml( $newValue );
			} elseif ( $op->getType() === 'remove' ) {
				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = $this->getClaimHtml( $oldValue );
				}
				$html .= $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $op->getType() === 'change' ) {
				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = $this->getClaimHtml( $newValue );
				}

				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = $this->getClaimHtml( $oldValue );
				}

				$html .= $this->generateChangeOpHtml( $oldValue, $newValue );
			}
			else {
				throw new \MWException( 'Invalid diffOp type' );
			}
		} else {
			$html = '';
			foreach ( $op as $key => $subOp ) {
				$html .= $this->generateOpHtml(
					array_merge( $path, array( $key ) ),
					$subOp
				);
			}
		}

		return $html;
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
	protected function getClaimHtml( Claim $claim ) {
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

	/**
	 * Get the label of an entity represented by its EntityId
	 *
	 * @since 0.4
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	protected function getEntityLabel( EntityId $id ) {
		$label = $id->getPrefixedId();

		$lookedUpLabel = self::$entityLookup->getEntity( $id )->getLabel(
			$this->getContext()->getLanguage()->getCode()
		);
		if ( $lookedUpLabel !== false ) {
			$label = $lookedUpLabel;
		}

		return $label;
	}
}