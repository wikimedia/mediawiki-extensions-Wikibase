<?php

namespace Wikibase\Test;

/**
 * DependencyTracker
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class DependencyTracker {

	private $declaredClasses = array();

	private $usedClasses = array();

	private $strings = array();

	function __construct() {
	}

	public function addDependenciesFromCode( $phpCode ) {
		$phpCode = $this->stripBlockComments( $phpCode );

		$this->declaredClasses += $this->extractDeclaredClasses( $phpCode );
		$this->usedClasses += $this->extractUsedClasses( $phpCode );
		$this->strings += $this->extractNameStrings( $phpCode );
	}

	/**
	 * @param string $phpCode
	 *
	 * @return string
	 */
	private function stripBlockComments( $phpCode ) {
		return preg_replace( '!/\*.*?\*/!s', '', $phpCode );
	}

	/**
	 * @param string $phpCode
	 *
	 * @return string[]
	 */
	private function extractDeclaredClasses( $phpCode ) {
		$namespace = '';
		$classes = array();

		if ( preg_match( '!^ *namespace +([\w\\\\]+) *;!mi', $phpCode, $m ) ) {
			$namespace = $m[1] . '\\';
		}

		preg_match_all( '!^ *class +(\w+) *([\r\n]+|\{| extends| implements)!mi', $phpCode, $matches, PREG_PATTERN_ORDER );

		if ( isset( $matches[1] ) ) {
			foreach ( $matches[1] as $name ) {
				$classes[] = $namespace . $name;
			}
		}

		return $classes;
	}

	/**
	 * @param string $phpCode
	 *
	 * @return string[]
	 */
	private function extractUsedClasses( $phpCode ) {
		$classes = array();

		preg_match_all( '!^ *use +([\w\\\\]+)(?: +as +(\w+))? *;!mi', $phpCode, $matches, PREG_PATTERN_ORDER );

		if ( isset( $matches[1] ) ) {
			foreach ( $matches[1] as $qname ) {
				$classes[] = $qname;
			}
		}

		//TODO: usage via implements & extends
		//TODO: usage via new and instanceof

		return $classes;
	}

	/**
	 * @param string $phpCode
	 *
	 * @return string[]
	 */
	private function extractNameStrings( $phpCode ) {
		$strings = array();

		preg_match_all( '!\'[\w\\\\]+\'|"[\w\\\\]+"!mi', $phpCode, $matches, PREG_PATTERN_ORDER );

		if ( isset( $matches[0] ) ) {
			foreach ( $matches[0] as $name ) {
				$strings[] = substr( str_replace( '\\\\', '\\', $name ), 1, -1 );
			}
		}

		return $strings;
	}

	/**
	 * @return string[]
	 */
	public function getDeclaredClasses() {
		return $this->declaredClasses;
	}

	/**
	 * @return string[]
	 */
	public function getNameStrings() {
		return $this->strings;
	}

	/**
	 * @return string[]
	 */
	public function getUsedClasses() {
		return $this->usedClasses;
	}


}
 