<?php

namespace Wikibase\View\Termbox;

use ResourceLoaderFileModule;

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
		$json = file_get_contents( $file );
		if ( $json === false ) {
			throw new Exception( "Unreadable file $file" );
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			throw new Exception( "Invalid JSON in $file" );
		}
		return $data;
	}

}
