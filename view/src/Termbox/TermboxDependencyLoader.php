<?php

namespace Wikibase\View\Termbox;

class TermboxDependencyLoader {
	/**
	 *
	 * @return string[]
	 */
	public static function getMessages( string $file ) {
		$JSON = self::readFileAsJSON( $file );
		if ( array_key_exists( 'messages', $JSON ) ) {
			return $JSON[ 'messages' ];
		} else {
			return [];
		}
	}

	/**
	 *
	 *
	 * @return string[] | null
	 */
	private static function readFileAsJSON( $file ) {
		if ( !is_readable( $file ) ) {
			return [];
		}

		$toParse = trim( file_get_contents( $file ) );
		if ( empty( $toParse ) ) {
			return [];
		}

		$JSON = json_decode( $toParse, true );
		if ( $JSON === null ) {
			return [];
		} else {
			return $JSON;
		}
	}
}
