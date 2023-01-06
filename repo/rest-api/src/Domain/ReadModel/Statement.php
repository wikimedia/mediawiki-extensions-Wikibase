<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\ReadModel;

use Wikibase\DataModel\ReferenceList;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Snak\SnakList;
use Wikibase\DataModel\Statement\StatementGuid;

/**
 * @license GPL-2.0-or-later
 */
class Statement {

	private StatementGuid $guid;
	private int $rank;
	private Snak $mainSnak;
	private SnakList $qualifiers;
	private ReferenceList $references;

	public function __construct(
		StatementGuid $guid,
		int $rank,
		Snak $mainSnak,
		SnakList $qualifiers,
		ReferenceList $references
	) {
		$this->guid = $guid;
		$this->rank = $rank;
		$this->mainSnak = $mainSnak;
		$this->qualifiers = $qualifiers;
		$this->references = $references;
	}

	public function getGuid(): StatementGuid {
		return $this->guid;
	}

	public function getRank(): int {
		return $this->rank;
	}

	public function getMainSnak(): Snak {
		return $this->mainSnak;
	}

	public function getQualifiers(): SnakList {
		return $this->qualifiers;
	}

	public function getReferences(): ReferenceList {
		return $this->references;
	}

}
