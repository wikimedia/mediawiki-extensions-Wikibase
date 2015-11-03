<?php

namespace Wikibase\Repo\Diff;

use Html;

/**
 * Class for generating diff rows for a given set of old and new values.
 *
 * @since 0.4
 *
 * @licence GNU GPL v2+
 * @author Tobias Gritschacher < tobias.gritschacher@wikimedia.de >
 * @author Katie Filbert < aude.wiki@gmail.com >
 * @author Daniel Kinzler
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
	 * Generates HTML for an change diffOp
	 *
	 * @return string HTML
	 */
	private function generateChangeOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->oldValues ) ) ) );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->newValues ) ) ) );
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an add diffOp
	 *
	 * @return string HTML
	 */
	private function generateAddOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'colspan' => '2' ), '&nbsp;' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->newValues ) )
			)
		);
		$html .= Html::closeElement( 'tr' );

		return $html;
	}

	/**
	 * Generates HTML for an remove diffOp
	 *
	 * @return string HTML
	 */
	private function generateRemoveOpHtml() {
		$html = Html::openElement( 'tr' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
		$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
			Html::rawElement( 'div', array(),
				Html::rawElement( 'del', array( 'class' => 'diffchange diffchange-inline' ),
					$this->generateValueHtml( $this->oldValues ) ) ) );
		$html .= Html::rawElement( 'td', array( 'colspan' => '2' ), '&nbsp;' );
		$html .= Html::closeElement( 'tr' );

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
				$html .= Html::rawElement( 'br', array(), '' );
			}
			$html .= Html::rawElement( 'span', array(), $value );
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

		if ( $this->oldValues !== null && $this->newValues !== null ) {
			$html .= $this->generateChangeOpHtml();
		} elseif ( $this->newValues !== null ) {
			$html .= $this->generateAddOpHtml();
		} elseif ( $this->oldValues !== null ) {
			$html .= $this->generateRemoveOpHtml();
		}

		return $html;
	}

}
