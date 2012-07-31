<?php

namespace Wikibase;
use Html;
use Diff\IDiff as IDiff;
use Diff\IDiffOp as IDiffOp;

/**
 * Class for generating views of IDiff objects.
 *
 * @since 0.1
 *
 * @file
 * @ingroup Wikibase
 *
 * @licence GNU GPL v2+
 * @author Daniel Kinzler
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
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
	 * @param IDiffOp $op
	 *
	 * @return string
	 * @throws \MWException
	 */
	protected function generateOpHtml( array $path, IDiffOp $op ) {
		if ( $op->isAtomic() ) {
			$name = implode( ' / ', $path ); #TODO: l10n

			$html = Html::openElement( 'tr' );
			$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );
			$html .= Html::element( 'td', array( 'colspan'=>'2', 'class' => 'diff-lineno' ), $name );

			//TODO: no path, but localized section title.

			//FIXME: complex objects as values?
			if ( $op->getType() === 'add' ) {
				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
							$op->getNewValue() ) ) );
				$html .= Html::closeElement( 'tr' );
			} elseif ( $op->getType() === 'remove' ) {
				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
					Html::rawElement( 'div', null,
						Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
							$op->getOldValue() ) ) );
				$html .= Html::closeElement( 'tr' );
			} elseif ( $op->getType() === 'change' ) {
				//TODO: use WordLevelDiff!

				$html .= Html::openElement( 'tr' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '-' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-deletedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'del', array( 'class' => 'diffchange diffchange-inline' ),
							$op->getOldValue() ) ) );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-marker' ), '+' );
				$html .= Html::rawElement( 'td', array( 'class' => 'diff-addedline' ),
					Html::rawElement( 'div', array(),
						Html::element( 'ins', array( 'class' => 'diffchange diffchange-inline' ),
							$op->getNewValue() ) ) );
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

}
