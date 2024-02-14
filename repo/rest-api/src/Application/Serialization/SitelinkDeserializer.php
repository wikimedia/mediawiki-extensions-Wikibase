<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\Serialization;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLink;

/**
 * @license GPL-2.0-or-later
 */
class SitelinkDeserializer {

	private string $invalidTitleRegex;

	public function __construct( string $invalidTitleRegex ) {
		$this->invalidTitleRegex = $invalidTitleRegex;
	}

	public function deserialize( string $siteId, array $serialization ): SiteLink {
		if ( !array_key_exists( 'title', $serialization ) ) {
			throw new MissingFieldException( 'title' );
		}

		$trimmedTitle = trim( $serialization[ 'title' ] );
		if ( empty( $trimmedTitle ) ) {
			throw new EmptySitelinkException( 'title', $trimmedTitle );
		}
		if ( preg_match( $this->invalidTitleRegex, $trimmedTitle ) === 1 ) {
			throw new InvalidFieldException( 'title', $trimmedTitle );
		}

		$serialization['badges'] ??= [];
		$badges = [];
		foreach ( $serialization[ 'badges' ] as $badge ) {
			$badges[] = new ItemId( $badge );
		}

		return new SiteLink( $siteId, $trimmedTitle, $badges );
	}

}
