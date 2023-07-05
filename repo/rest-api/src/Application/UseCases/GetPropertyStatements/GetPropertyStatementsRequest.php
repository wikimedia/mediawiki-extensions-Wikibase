<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Application\UseCases\GetPropertyStatements;

/**
 * @license GPL-2.0-or-later
 */
class GetPropertyStatementsRequest {

	private string $subjectPropertyId;
	private ?string $filterPropertyId;

	public function __construct( string $subjectPropertyId, ?string $filterPropertyId = null ) {
		$this->subjectPropertyId = $subjectPropertyId;
		$this->filterPropertyId = $filterPropertyId;
	}

	public function getSubjectPropertyId(): string {
		return $this->subjectPropertyId;
	}

	public function getFilterPropertyId(): ?string {
		return $this->filterPropertyId;
	}

}
