<?php declare( strict_types = 1 );

namespace Wikibase\Repo\Domains\Reuse\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class Qualifiers {

	private array $qualifiers;

	public function __construct( PropertyValuePair ...$qualifiers ) {
		$this->qualifiers = $qualifiers;
	}

	/**
	 * @return PropertyValuePair[]
	 */
	public function getQualifiersByPropertyId( PropertyId $id ): array {
		return array_filter(
			$this->qualifiers,
			fn( PropertyValuePair $q ) => $q->property->id->equals( $id )
		);
	}

}
