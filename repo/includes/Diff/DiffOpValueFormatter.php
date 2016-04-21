<?php

namespace Wikibase\Repo\Diff;

use Html;

/**
 * Class for generating diff rows for a given set of old and new values.
 *
 * @since 0.4
 *
 * @license GPL-2.0+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
 * @author Thiemo Mättig
 */
class DiffOpValueFormatter {

	/**
	 * @var string
	 */
	private $oldName;

	/**
	 * @var string
	 */
	private $newName;

	/**
	 * @var string[]|null
	 */
	private $oldValues;

	/**
	 * @var string[]|null
	 */
	private $newValues;

	/**
	 * @since 0.4
	 *
	 * @param string $oldName HTML of old name
	 * @param string $newName HTML of new name
	 * @param string|string[]|null $oldValuesHtml HTML of old value(s)
	 * @param string|string[]|null $newValuesHtml HTML of new value(s)
	 */
	public function __construct( $oldName, $newName, $oldValuesHtml, $newValuesHtml ) {
		$this->oldName = $oldName;
		$this->newName = $newName;
		$this->oldValues = is_string( $oldValuesHtml ) ? array( $oldValuesHtml ) : $oldValuesHtml;
		$this->newValues = is_string( $newValuesHtml ) ? array( $newValuesHtml ) : $newValuesHtml;
	}

	/**
	 * Generates HTML for the header of the diff operation
	 *
	 * @return string HTML
	 */
	private function generateHeaderHtml() {
		$oldHeader = is_array( $this->oldValues ) ? $this->oldName : '';
		$newHeader = is_array( $this->newValues ) ? $this->newName : '';

		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan' => '2', 'class' => 'diff-lineno' ), $oldHeader );
		$html .= Html::rawElement( 'td', array( 'colspan' => '2', 'class' => 'diff-lineno' ), $newHeader );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * @return string HTML
	 */
	private function generateDeletedCells() {
		$html = Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', [],
				Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->oldValues )
				)
			)
		);

		return $html;
	}

	/**
	 * @return string HTML
	 */
	private function generateAddedCells() {
		$html = Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', [],
				Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->newValues )
				)
			)
		);

		return $html;
	}

	/**
	 * @return string HTML
	 */
	private function generateEmptyCells() {
		$html = Html::rawElement( 'td', array( 'colspan' => '2' ), '&nbsp;' );

		return $html;
	}

	/**
	 * Generates HTML from a given value or array of values
	 *
	 * @param string[] $values HTML
	 *
	 * @return string HTML
	 */
	private function generateValueHtml( array $values ) {
		$html = '';

		foreach ( $values as $value ) {
			if ( $html !== '' ) {
				$html .= Html::rawElement( 'br', [], '' );
			}
			$html .= Html::rawElement( 'span', [], $value );
		}

		return $html;
	}

	/**
	 * Generates HTML for a diffOP
	 *
	 * @since 0.4
	 *
	 * @return string HTML
	 */
	public function generateHtml() {
		$html = $this->generateHeaderHtml();
		$html .= Html::openElement( 'tr' );

		if ( $this->oldValues === null ) {
			$html .= $this->generateEmptyCells();
		} else {
			$html .= $this->generateDeletedCells();
		}

		if ( $this->newValues === null ) {
			$html .= $this->generateEmptyCells();
		} else {
			$html .= $this->generateAddedCells();
		}

		$html .= Html::closeElement( 'tr' );

		return $html;
	}

}
