<?php

namespace Wikibase;

/**
 * Interface for diffs. Diffs are collections of IDiffOp objects.
 *
 * @since 0.1
 *
 * @file
 * @ingroup WikibaseLib
 * @ingroup WikibaseDiff
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface IDiff {

	/**
	 * @since 0.1
	 * @param $operations array of IDiffOp
	 */
	function __construct( array $operations );

	/**
	 * @since 0.1
	 * @return array of IDiffOp
	 */
	public function getOperations();

	/**
	 * @since 0.1
	 * @return boolean
	 */
	public function isEmpty();

}