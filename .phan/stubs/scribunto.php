<?php

/**
 * Minimal set of classes necessary to fulfill needs of parts of Wikibase relying on
 * the Scribunto extension.
 */

namespace {

	use MediaWiki\Extension\Scribunto\ScribuntoException;

	class ScribuntoEngineBase {
		/**
		 * @return Parser
		 */
		public function getParser() : Parser {
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
		public function __construct( $message, array $options = [] ) {
		}
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
		 * @return Parser
		 */
		protected function getParser() {
		}

		/**
		 * @return ParserOptions
		 */
		protected function getParserOptions() {
		}

		/**
		 * @return array Lua package
		 */
		public function register() {
		}

	}
}


namespace MediaWiki\Extension\Scribunto {

	use MWException;

	class ScribuntoException extends MWException {

		/**
		 * @param string $messageName
		 * @param array $params
		 */
		public function __construct( $messageName, $params = [] ) {
		}

	}
}
