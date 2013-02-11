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
				$name = $path[0] . ' / ' . Html::element( 'i', array(), $this->getEntityLabel( $propertyId ) );
			} else {
				$name = implode( ' / ', $path ); // TODO: l10n
			}

			$diffHtml = '';

			if ( $op->getType() === 'add' ) {
				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = $this->getSnakHtml( $newValue->getMainSnak() );
				}
				$diffHtml .= $this->generateAddOpHtml( $newValue );
			} elseif ( $op->getType() === 'remove' ) {
				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = $this->getSnakHtml( $oldValue->getMainSnak() );
				}
				$diffHtml .= $this->generateRemoveOpHtml( $oldValue );
			} elseif ( $op->getType() === 'change' ) {
				$newValue = $op->getNewValue();
				$oldValue = $op->getOldValue();

				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$differ = new \Diff\MapDiffer();
					$claimDiff = $differ->doDiff( $oldValue->toArray(), $newValue->toArray() );

					if ( array_key_exists( 'm', $claimDiff ) ) {
						$newValue = $this->getSnakHtml( $newValue->getMainSnak() );
						$oldValue = $this->getSnakHtml( $oldValue->getMainSnak() );
					} elseif ( array_key_exists( 'refs', $claimDiff ) ) {
						$name .= ' / ' . wfMessage( 'wikibase-diffview-references' )->escaped();
						$newValue = $this->getRefsHtml( $newValue->getReferences() );
						$oldValue = $this->getRefsHtml( $oldValue->getReferences() );
					} elseif ( array_key_exists( 'q', $claimDiff ) ) {
						throw new \MWException( 'Diff of qualifiers not implemented' );
					} else {
						throw new \MWException( 'Invalid Snak type' );
					}
				}

				$diffHtml .= $this->generateChangeOpHtml( $oldValue, $newValue );
			}
			else {
				throw new \MWException( 'Invalid diffOp type' );
			}
			$html = $this->generateDiffHeaderHtml( $name ) . $diffHtml;
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
	 * Get HTML for references
	 *
	 * @since 0.4
	 *
	 * @param ReferenceList $refList
	 *
	 * @return string
	 */
	private function getRefsHtml( ReferenceList $refList ) {
		$html = '';
		foreach ( $refList as $ref ) {
			$refSnakList = $ref->getSnaks();
			foreach ( $refSnakList as $snak ) {
				if ( $html !== '' ) {
					$html .= Html::rawElement( 'br', array(), '' );
				}
				$html .= $this->getSnakHtml( $snak );
			}
			$html .= Html::rawElement( 'br', array(), '' );
		}

		return $html;
	}

	/**
	 * Get HTML for a changed snak
	 *
	 * @since 0.4
	 *
	 * @param Snak $snak
	 *
	 * @return string
	 */
	protected function getSnakHtml( Snak $snak ) {
		$snakType = $snak->getType();
		$diffValueString = Html::rawElement( 'i', array(), $snakType );

		if ( $snakType === 'value' ) {
			$dataValue = $snak->getDataValue();

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