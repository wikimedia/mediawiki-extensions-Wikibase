<?php declare( strict_types=1 );

namespace Wikibase\Repo\RestApi\Domain\Model;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\SiteLinkList;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\AliasGroupList;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0-or-later
 */
class ItemData {
	public const FIELD_TYPE = 'type';
	public const FIELD_LABELS = 'labels';
	public const FIELD_DESCRIPTIONS = 'descriptions';
	public const FIELD_ALIASES = 'aliases';
	public const FIELD_STATEMENTS = 'statements';
	public const FIELD_SITELINKS = 'sitelinks';
	public const VALID_FIELDS = [
		self::FIELD_TYPE,
		self::FIELD_LABELS,
		self::FIELD_DESCRIPTIONS,
		self::FIELD_ALIASES,
		self::FIELD_STATEMENTS,
		self::FIELD_SITELINKS,
	];

	private ItemId $id;
	private array $requestedFields;
	private ?string $type;
	private ?TermList $labels;
	private ?TermList $descriptions;
	private ?AliasGroupList $aliases;
	private ?StatementList $statements;
	private ?SiteLinkList $siteLinks;

	public function __construct(
		ItemId $id,
		array $requestedFields,
		?string $type,
		?TermList $labels,
		?TermList $descriptions,
		?AliasGroupList $aliases,
		?StatementList $statements,
		?SiteLinkList $siteLinks
	) {
		$this->id = $id;
		$this->requestedFields = $requestedFields;
		$this->type = $type;
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
		$this->statements = $statements;
		$this->siteLinks = $siteLinks;
	}

	public function getId(): ItemId {
		return $this->id;
	}

	public function getType(): ?string {
		return $this->type;
	}

	public function getLabels(): ?TermList {
		return $this->labels;
	}

	public function getDescriptions(): ?TermList {
		return $this->descriptions;
	}

	public function getAliases(): ?AliasGroupList {
		return $this->aliases;
	}

	public function getStatements(): ?StatementList {
		return $this->statements;
	}

	public function getSiteLinks(): ?SiteLinkList {
		return $this->siteLinks;
	}

	public function isRequested( string $field ): bool {
		return in_array( $field, $this->requestedFields );
	}
}
