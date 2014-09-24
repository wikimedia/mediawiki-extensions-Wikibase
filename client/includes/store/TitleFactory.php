<?php

namespace Wikibase\Client\Store;

use InvalidArgumentException;
use Title;
use Wikibase\Lib\Store\StorageException;

/**
 * Service for constructing Title objects from page IDs or title strings.
 * This should be used instead of the static factory methods in the Title class,
 * to allow the title construction process to be overwritten during testing.
 *
 * @todo: move this into MediaWiki core.
 *
 * @license GPL 2+
 * @author Daniel Kinzler
 */
class TitleFactory {

	/**
	 * @see Title::newFromID
	 *
	 * @param int $pageId
	 *
	 * @throws StorageException
	 * @return Title
	 */
	public function newFromID( $pageId ) {
		$title = Title::newFromID( $pageId );

		if ( !$title ) {
			throw new StorageException( "No page found for ID $pageId." );
		}

		return $title;
	}

	/**
	 * @see Title::newFromText
	 *
	 * @param string $text
	 * @param int $defaultNamespace
	 *
	 * @throws StorageException
	 * @return Title
	 */
	public function newFromText( $text, $defaultNamespace = NS_MAIN ) {
		$title = Title::newFromText( $text, $defaultNamespace );

		if ( !$title ) {
			throw new StorageException( "Failed to construct a Title for text `$text`." );
		}

		return $title;
	}

	/**
	 * @see Title::makeTitle
	 * @note: Use this only with values that can be assumed to be safe and already validated!
	 * For unsafe values, use makeTitleSafe() instead.
	 *
	 * @param int $ns
	 * @param string $text
	 * @param string $fragment
	 * @param string $interwiki (deprecated, do not use)
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return Title
	 */
	public function makeTitle( $ns, $text, $fragment = '', $interwiki = '' ) {
		if ( $interwiki !== '' ) {
			throw new InvalidArgumentException( 'TitleFactory does not support interwiki links!' );
		}

		$title = Title::makeTitle( $ns, $text, $fragment, $interwiki );

		if ( !$title ) {
			throw new StorageException( "Failed to make a Title for text `$text` in namespace $ns." );
		}

		return $title;
	}

	/**
	 * @see Title::makeTitleSafe
	 * @note: If all parameters have been validated and can be assumed to be safe,
	 * makeTitle() can be used, which should be a little faster.
	 *
	 * @param int $ns
	 * @param string $text
	 * @param string $fragment
	 * @param string $interwiki (deprecated, do not use)
	 *
	 * @throws InvalidArgumentException
	 * @throws StorageException
	 * @return Title
	 */
	public function makeTitleSafe( $ns, $text, $fragment = '', $interwiki = '' ) {
		if ( $interwiki !== '' ) {
			throw new InvalidArgumentException( 'TitleFactory does not support interwiki links!' );
		}

		$title = Title::makeTitleSafe( $ns, $text, $fragment, $interwiki );

		if ( !$title ) {
			throw new StorageException( "Failed to make a Title for text `$text` in namespace $ns." );
		}

		return $title;
	}

}
