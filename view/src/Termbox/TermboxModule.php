<?php

namespace Wikibase\View\Termbox;

use Exception;
use ResourceLoaderFileModule;
use Wikimedia;

/**
 * @license GPL-2.0-or-later
 */
class TermboxModule extends ResourceLoaderFileModule {

	/** @return string[] */
	public function getMessages() {
		$data = $this->readJsonFile( $this->getLocalPath( 'resources.json' ) );
		return array_merge(
			parent::getMessages(),
			$data['messages']
		);
	}

	/**
	 * @return string[] | null
	 * @throws Exception If the file is not valid JSON
	 */
	private function readJsonFile( $file ) {
		Wikimedia\restoreWarnings();
		$json = file_get_contents( $file );
		Wikimedia\restoreWarnings();
		if ( $json === false ) {
			throw new Exception( "Failed to open $file" );
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			throw new Exception( "Failed to parse $file" );
		}
		return $data;
	}

}
