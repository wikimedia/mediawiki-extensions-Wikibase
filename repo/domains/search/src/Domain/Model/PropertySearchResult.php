<?php declare( strict_types=1 );

namespace Wikibase\Repo\Domains\Search\Domain\Model;

use Wikibase\DataModel\Entity\PropertyId;

/**
 * @license GPL-2.0-or-later
 */
class PropertySearchResult {

	private PropertyId $propertyId;
	private ?Label $label;
	private ?Description $description;
	private MatchedData $matchedData;

	public function __construct(
		PropertyId $propertyId,
		?Label $label,
		?Description $description,
		MatchedData $matchedData
	) {
		$this->propertyId = $propertyId;
		$this->label = $label;
		$this->description = $description;
		$this->matchedData = $matchedData;
	}

	public function getPropertyId(): PropertyId {
		return $this->propertyId;
	}

	public function getLabel(): ?Label {
		return $this->label;
	}

	public function getDescription(): ?Description {
		return $this->description;
	}

	public function getMatchedData(): MatchedData {
		return $this->matchedData;
	}
}
