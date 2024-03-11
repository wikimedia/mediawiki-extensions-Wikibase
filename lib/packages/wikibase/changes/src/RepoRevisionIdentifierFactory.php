<?php

declare( strict_types = 1 );
namespace Wikibase\Lib\Changes;

use InvalidArgumentException;

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
	 */
	public function newFromArray( array $data ): RepoRevisionIdentifier {
		if ( $data['arrayFormatVersion'] !== RepoRevisionIdentifier::ARRAYFORMATVERSION ) {
			throw new InvalidArgumentException( 'Unsupported format version ' . $data['arrayFormatVersion'] );
		}

		return new RepoRevisionIdentifier(
			$data['entityIdSerialization'],
			wfTimestamp( TS_MW, $data['revisionTimestamp'] ),
			$data['revisionId']
		);
	}

}
