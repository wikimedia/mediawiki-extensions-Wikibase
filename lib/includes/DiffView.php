<?php

namespace Wikibase;
use Html;
use Diff\IDiff;
use Diff\DiffOp;

/**
 * Class for generating views of DiffOp objects.
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
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 */
class DiffView extends \ContextSource {

	/**
	 * @since 0.1
	 *
	 * @var array
	 */
	protected $path;

	/**
	 * @since 0.1
	 *
	 * @var IDiff
	 */
	protected $diff;

	/**
	 * @since 0.4
	 *
	 * @var WikiPageEntityLookup
	 */
	protected $entityLookup;

	/**
	 * Constructor.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param IDiff $diff
	 * @param \IContextSource|null $contextSource
	 */
	public function __construct( array $path, IDiff $diff, \IContextSource $contextSource = null ) {
		$this->path = $path;
		$this->diff = $diff;

		if ( !is_null( $contextSource ) ) {
			$this->setContext( $contextSource );
		}

		$this->entityLookup = new \Wikibase\WikiPageEntityLookup();
	}

	/**
	 * Builds and returns the HTML to represent the Diff.
	 *
	 * @since 0.1
	 *
	 * @return string
	 */
	public function getHtml() {
		return $this->generateOpHtml( $this->path, $this->diff );
	}

	/**
	 * Does the actual work.
	 *
	 * @since 0.1
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 * @throws \MWException
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			$html = $this->generateDiffHeaderHtml( $path, $op );

			//TODO: no path, but localized section title.

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = $this->getChangedSnakHtml( $newValue );
				}

				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
							$newValue ) ) );
				$html .= Html::closeElement( 'tr' );
			} elseif ( $op->getType() === 'remove' ) {
				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = $this->getChangedSnakHtml( $oldValue );
				}

				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
							$oldValue ) ) );
				$html .= Html::rawElement( 'td', array( 'colspan'=>'2' ), '&nbsp;' );
				$html .= Html::closeElement( 'tr' );
			} elseif ( $op->getType() === 'change' ) {
				//TODO: use WordLevelDiff!

				$newValue = $op->getNewValue();
				if ( !is_string( $newValue ) && $path[0] === 'claim' ) {
					$newValue = $this->getChangedSnakHtml( $newValue );
				}

				$oldValue = $op->getOldValue();
				if ( !is_string( $oldValue ) && $path[0] === 'claim' ) {
					$oldValue = $this->getChangedSnakHtml( $oldValue );
				}

				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
							$oldValue ) ) );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
							$newValue ) ) );
				$html .= Html::closeElement( 'tr' );
				$html .= Html::closeElement( 'tr' );

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
	 * @param Statement $statement
	 *
	 * @return string
	 */
	private function getChangedSnakHtml( Statement $statement ) {
		$snakType = $statement->getMainSnak()->getType();
		$diffValueString = $snakType;

		if ( $snakType === 'value' ) {
			$dataValue = $statement->getMainSnak()->getDataValue();
			if ( $dataValue instanceof EntityId ) {
				$diffValueString = $this->getEntityLabel( $dataValue );
			} else {
				$diffValueString = $dataValue->getValue();
			}
		}

		return $diffValueString;
	}

	/**
	 * Builds the HTML for the header of a specific diff operation
	 *
	 * @since 0.4
	 *
	 * @param array $path
	 * @param DiffOp $op
	 *
	 * @return string
	 */
	private function generateDiffHeaderHtml( array $path, DiffOp $op ) {
		if ( $path[0] === 'claim' ) {
			if ( $op->getType() === 'change' ) {
				$propertyLabel = $this->getEntityLabel( $op->getOldValue()->getPropertyId() );
				$name = $path[0] . ' / ' . $propertyLabel;
			} elseif ( $op->getType() === 'add' ) {
				$propertyLabel = $this->getEntityLabel( $op->getNewValue()->getPropertyId() );
				$name = $path[0] . ' / ' . $propertyLabel;
			} elseif ( $op->getType() === 'remove' ) {
				$propertyLabel = $this->getEntityLabel( $op->getOldValue()->getPropertyId() );
				$name = $path[0] . ' / ' . $propertyLabel;
			}
		} else {
			$name = implode( ' / ', $path ); // TODO: l10n
		}

		$html = Html::openElement( 'tr' );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Gets the label of an entity represented by its EntityId
	 *
	 * @since 0.4
	 *
	 * @param EntityId $id
	 *
	 * @return string
	 */
	private function getEntityLabel( EntityId $id ) {
		$label = $this->entityLookup->getEntity( $id )->getLabel(
			$this->getContext()->getLanguage()->getCode()
		);

		if ( $label === false ) {
			$label = $id->getPrefixedId();
		}

		return $label;
	}

}
