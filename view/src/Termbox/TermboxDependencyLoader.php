<?php

namespace Wikibase\View\Termbox;

use ResourceLoaderFileModule;

/**
 * @license GPL-2.0-or-later
 */
class TermboxDependencyLoader extends ResourceLoaderFileModule {

	public function __construct( array $options = [], $localBasePath = null, $remoteBasePath = null ) {
		parent::__construct( $options, $localBasePath, $remoteBasePath );

		if ( !array_key_exists( 'data', $options ) ) {
			return;
		}

		$config = $this->readJsonFile(
			$this->localBasePath . DIRECTORY_SEPARATOR . $options['data']
		);

		$this->messages = $config['messages'] ?? [];
	}

	/**
	 * @return string[] | null
	 */
	private function readJsonFile( $file ) {
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
