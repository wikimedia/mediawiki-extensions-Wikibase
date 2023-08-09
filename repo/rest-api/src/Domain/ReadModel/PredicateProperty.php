<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PredicateProperty {

	private PropertyId $id;
	private ?string $dataType;

	public function __construct( PropertyId $id, ?string $dataType ) {
		$this->id = $id;
		$this->dataType = $dataType;
	}

	public function getId(): PropertyId {
		return $this->id;
	}

	/**
	 * @return string|null null only if the property was deleted/cannot be found
	 */
	public function getDataType(): ?string {
		return $this->dataType;
	}

}
