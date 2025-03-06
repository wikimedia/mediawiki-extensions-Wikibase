<?php

namespace Wikibase\View\Termbox;

use MediaWiki\ResourceLoader as RL;
use RuntimeException;

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
	 * @param string $file
	 * @return string[][]|null
	 */
	private function readJsonFile( string $file ) {
		// phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
		$json = @file_get_contents( $file );
		if ( $json === false ) {
			throw new RuntimeException( "Failed to open $file" );
		}
		$data = json_decode( $json, true );
		if ( $data === null ) {
			throw new RuntimeException( "Failed to parse $file" );
		}
		return $data;
	}

}
