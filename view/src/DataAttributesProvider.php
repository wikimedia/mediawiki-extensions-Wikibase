<?php

namespace Wikibase\View;

use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

/**
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
abstract class DataAttributesProvider {

	/**
	 * @param object $object
	 *
	 * @throws UnexpectedValueException if
	 * @return string HTML fragment
	 */
	public function getHtml( $object ) {
		$html = '';

		foreach ( $this->getDataAttributes( $object ) as $attr => $data ) {
			if ( !is_string( $attr ) || !preg_match( '/^data-[\w-]+$/u', $attr ) ) {
				throw new UnexpectedValueException( "\"$attr\" is not a valid data attribute name" );
			}

			$html .= " $attr=\"" . htmlspecialchars( $data ) . '"';
		}

		return $html;
	}

	/**
	 * @param object $object
	 *
	 * @throws InvalidArgumentException if an object of an unexpected type was provided
	 * @return string[] Array mapping data attributes names to values.
	 */
	abstract protected function getDataAttributes( $object );

}
