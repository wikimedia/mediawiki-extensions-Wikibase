<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Changes;

use MWException;

/**
 * Factory for RepoRevisionIdentifier objects.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class RepoRevisionIdentifierFactory {

	/**
	 * @see RepoRevisionIdentifier::toArray
	 *
	 * @param array $data
	 * @return RepoRevisionIdentifier
	 *
	 * @throws MWException
	 */
	public function newFromArray( array $data ): RepoRevisionIdentifier {
		if ( $data['arrayFormatVersion'] !== RepoRevisionIdentifier::ARRAYFORMATVERSION ) {
			throw new MWException( 'Unsupported format version ' . $data['arrayFormatVersion'] );
		}

		return new RepoRevisionIdentifier(
			$data['entityIdSerialization'],
			$data['revisionTimestamp'],
			$data['revisionId'],
			$data['revisionParentId']
		);
	}

}
