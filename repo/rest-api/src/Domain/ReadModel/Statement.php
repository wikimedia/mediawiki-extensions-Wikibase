<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class Statement {

	private StatementGuid $guid;
	private Property $property;
	private Value $value;
	private Rank $rank;
	private Qualifiers $qualifiers;
	private ReferenceList $references;

	public function __construct(
		StatementGuid $guid,
		Property $property,
		Value $value,
		Rank $rank,
		Qualifiers $qualifiers,
		ReferenceList $references
	) {
		$this->guid = $guid;
		$this->property = $property;
		$this->value = $value;
		$this->rank = $rank;
		$this->qualifiers = $qualifiers;
		$this->references = $references;
	}

	public function getGuid(): StatementGuid {
		return $this->guid;
	}

	public function getProperty(): Property {
		return $this->property;
	}

	public function getValue(): Value {
		return $this->value;
	}

	public function getRank(): Rank {
		return $this->rank;
	}

	public function getQualifiers(): Qualifiers {
		return $this->qualifiers;
	}

	public function getReferences(): ReferenceList {
		return $this->references;
	}

}
