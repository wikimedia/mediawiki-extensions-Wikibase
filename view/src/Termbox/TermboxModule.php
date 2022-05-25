<?php

namespace Wikibase\View\Termbox;

use Exception;
use MediaWiki\ResourceLoader as RL;

/**
 * @license GPL-2.0-or-later
 */
class TermboxModule extends RL\FileModule {

	/** @return string[] */
	public function getMessages() {
		$data = $this->readJsonFile( $this->getLocalPath( 'resources.json' ) );
		'@phan-var array[] $data';
		return array_merge(
			parent::getMessages(),
			$data['messages']
		);
	}

	/**
	 * @return string[][]|null
	 * @throws Exception If the file is not valid JSON
	 */
	private function readJsonFile( $file ) {
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$json = @file_get_contents( $file );
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
