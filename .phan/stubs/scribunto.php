<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Scribunto extension.
 * @codingStandardsIgnoreFile
 */

class ScribuntoEngineBase {
	/**
	 * @return Parser
	 */
	public function getParser() {
	}
}

class Scribunto_LuaEngine extends ScribuntoEngineBase {

	/**
	 * @param string $moduleFileName
	 * @param array $interfaceFuncs
	 * @param array $setupOptions
	 *
	 * @return array Lua package
	 */
	public function registerInterface( $moduleFileName, $interfaceFuncs, $setupOptions = [] ) {
	}

}

class Scribunto_LuaError extends ScribuntoException {
}

class Scribunto_LuaLibraryBase {

	/**
	 * @param string $name
	 * @param int $argIdx
	 * @param mixed $arg
	 * @param string $expectType
	 */
	protected function checkType( $name, $argIdx, $arg, $expectType ) {
	}

	/**
	 * @param string $name
	 * @param int $argIdx
	 * @param mixed &$arg
	 * @param string $expectType
	 * @param mixed $default
	 */
	protected function checkTypeOptional( $name, $argIdx, &$arg, $expectType, $default ) {
	}

	/**
	 * @return Scribunto_LuaEngine engine
	 */
	protected function getEngine() {
	}

	/**
	 * @return Parser parser
	 */
	protected function getParser() {
	}

	/**
	 * @return ParserOptions parser options
	 */
	protected function getParserOptions() {
	}

	/**
	 * @return array Lua package
	 */
	function register() {
	}

}

class ScribuntoException extends MWException {

	/**
	 * @param string $messageName
	 * @param array $params
	 */
	function __construct( $messageName, $params = [] ) {
	}

}
