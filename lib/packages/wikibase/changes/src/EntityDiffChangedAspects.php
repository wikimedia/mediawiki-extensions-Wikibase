<?php

namespace Wikibase\Lib\Changes;

use InvalidArgumentException;
use Serializable;
use Wikimedia\Assert\Assert;

/**
 * This class holds a very compact and simple representation of an Entity diff for
 * propagating repo changes to clients (T113468).
 * This can also be used for entity types which don't have all aspects mentioned here,
 * the aspects are represented as unchanged in that case.
 *
 * @license GPL-2.0-or-later
 * @author Marius Hoch
 */
class EntityDiffChangedAspects implements Serializable {

	/**
	 * Increases whenever the array format (self::toArray) changes
	 */
	public const ARRAYFORMATVERSION = 1;

	/**
	 * Language codes of the labels that changed (added, removed or updated)
	 *
	 * @var string[]
	 */
	private $labelChanges;

	/**
	 * Language codes of the descriptions that changed (added, removed or updated)
	 *
	 * @var string[]
	 */
	private $descriptionChanges;

	/**
	 * Language codes of the aliases that changed (added, removed or updated)
	 *
	 * @var string[]
	 */
	private $aliasChanges;

	/**
	 * Property id serialization from the statements that changed (added, removed or updated) - this excludes any changes that are
	 * purely qual/ref
	 *
	 * @var string[]
	 */
	private $statementChangesExcludingQualOrRefOnlyChanges;

	/**
	 * Property id serialization from the statements that changed (added, removed or updated) that are purely
	 * qual/ref changes. This can be combined with statementChanges above for the full statement changes
	 *
	 * @var string[]
	 */
	private $statementChangesQualOrRefOnly;

	/**
	 * Map of site ids to array of old value, new value and boolean value determining if badge
	 * has changed or not
	 *
	 * @var array<string,array{0: ?string, 1: ?string, 2: bool}>
	 */
	private $siteLinkChanges;

	/**
	 * Other changes that are not covered in above
	 *
	 * @var bool
	 */
	private $otherChanges;

	/**
	 * Note: If an entity doesn't have a certain aspect, just report that no changes happened (empty array).
	 *
	 * @param string[] $labelChanges Language codes of the labels that changed (added, removed or updated)
	 * @param string[] $descriptionChanges Language codes of the descriptions that changed (added, removed or updated)
	 * @param string[] $aliasChanges Language codes of the aliases that changed (added, removed or updated)
	 * @param string[] $statementChangesExcludingQualOrRefOnlyChanges Property id serialization from the statements that changed (added,
	 * removed or updated), excluding statement changes which are qualifier/ref only
	 * @param string[] $statementChangesQualOrRefOnly Property id serialization from the statements that changed
	 * (added, removed or updated) - only changes that are purely qualifier/ref changes
	 * @param array<string,array{0: ?string, 1: ?string, 2: bool}> $siteLinkChanges Map of global site identifiers to
	 * 	[ string|null $oldPageName, string|null $newPageName, bool $badgesChanged ]
	 * @param bool $otherChanges Do we have changes that are not covered more specifically?
	 */
	public function __construct(
		array $labelChanges,
		array $descriptionChanges,
		array $aliasChanges,
		array $statementChangesExcludingQualOrRefOnlyChanges,
		array $statementChangesQualOrRefOnly,
		array $siteLinkChanges,
		$otherChanges
	) {
		Assert::parameterElementType( 'string', $labelChanges, '$labelChanges' );
		Assert::parameterElementType( 'string', $descriptionChanges, '$descriptionChanges' );
		Assert::parameterElementType( 'string', $aliasChanges, '$aliasChanges' );
		Assert::parameterElementType(
			'string', $statementChangesExcludingQualOrRefOnlyChanges, '$statementChangesExcludingQualOrRefOnlyChanges'
		);
		Assert::parameterElementType( 'string', $statementChangesQualOrRefOnly, '$statementChangesQualOrRefOnly' );
		Assert::parameterKeyType( 'string', $siteLinkChanges, '$siteLinkChanges' );
		Assert::parameterElementType( 'array', $siteLinkChanges, '$siteLinkChanges' );
		Assert::parameterType( 'boolean', $otherChanges, '$otherChanges' );

		$this->labelChanges = $labelChanges;
		$this->descriptionChanges = $descriptionChanges;
		$this->aliasChanges = $aliasChanges;
		$this->statementChangesExcludingQualOrRefOnlyChanges = $statementChangesExcludingQualOrRefOnlyChanges;
		$this->statementChangesQualOrRefOnly = $statementChangesQualOrRefOnly;
		$this->siteLinkChanges = $siteLinkChanges;
		$this->otherChanges = $otherChanges;
	}

	/**
	 * Language codes of the labels that changed (added, removed or updated)
	 *
	 * @return string[]
	 */
	public function getLabelChanges() {
		return $this->labelChanges;
	}

	/**
	 * Language codes of the descriptions that changed (added, removed or updated)
	 *
	 * @return string[]
	 */
	public function getDescriptionChanges() {
		return $this->descriptionChanges;
	}

	/**
	 * Language codes of the aliases that changed (added, removed or updated)
	 *
	 * @return string[]
	 */
	public function getAliasChanges() {
		return $this->aliasChanges;
	}

	/**
	 * Property id serialization from the statements that changed (added, removed or updated) NOTE: this includes all statement changes,
	 * whether they are qual/ref only or not
	 *
	 * @return string[]
	 */
	public function getStatementChanges() {
		return array_merge( $this->statementChangesExcludingQualOrRefOnlyChanges, $this->statementChangesQualOrRefOnly );
	}

	/**
	 * Property id serialization from the statements that changed (added, removed or updated) excluding those that are qual/ref only
	 *
	 * @return string[]
	 */
	public function getStatementChangesExcludingQualOrRefOnly() {
		return $this->statementChangesExcludingQualOrRefOnlyChanges;
	}

	/**
	 * Property id serialization from the statements that were updated that are qual/ref only
	 *
	 * @return string[]
	 */
	public function getStatementChangesQualOrRefOnly() {
		return $this->statementChangesQualOrRefOnly;
	}

	/**
	 * Map of site ids to array of old value, new value and boolean value determining if badge
	 * has changed or not
	 *
	 * @return array<string,array{0: ?string, 1: ?string, 2: bool}>
	 */
	public function getSiteLinkChanges() {
		return $this->siteLinkChanges;
	}

	/**
	 * Do we have changes that are not covered more specifically?
	 *
	 * @return bool
	 */
	public function hasOtherChanges() {
		return $this->otherChanges;
	}

	/**
	 * @see Serializable::serialize
	 *
	 * @return string JSON
	 */
	public function serialize(): string {
		return json_encode( $this->__serialize() );
	}

	public function __serialize(): array {
		return $this->toArray();
	}

	/**
	 * @see Serializable::unserialize
	 *
	 * @param string $serialized JSON
	 */
	public function unserialize( $serialized ) {
		$this->__unserialize( json_decode( $serialized, true ) );
	}

	public function __unserialize( array $data ): void {
		if ( $data['arrayFormatVersion'] !== self::ARRAYFORMATVERSION ) {
			throw new InvalidArgumentException( 'Unsupported format version ' . $data['arrayFormatVersion'] );
		}

		$this->labelChanges = $data['labelChanges'];
		$this->descriptionChanges = $data['descriptionChanges'];
		$this->aliasChanges = $data['aliasChanges'] ?? [];
		$this->statementChangesExcludingQualOrRefOnlyChanges = array_values(
			(array)$data['statementChangesExcludingQualOrRefOnlyChanges']
		);
		$this->statementChangesQualOrRefOnly = array_values( (array)$data['statementChangesQualOrRefOnly'] );
		$this->siteLinkChanges = (array)$data['siteLinkChanges'];
		$this->otherChanges = $data['otherChanges'];
	}

	public function toArray(): array {
		return [
			'arrayFormatVersion' => self::ARRAYFORMATVERSION,
			'labelChanges' => $this->getLabelChanges(),
			'descriptionChanges' => $this->getDescriptionChanges(),
			'aliasChanges' => $this->getAliasChanges(),
			'statementChangesExcludingQualOrRefOnlyChanges' => $this->getStatementChangesExcludingQualOrRefOnly(),
			'statementChangesQualOrRefOnly' => $this->getStatementChangesQualOrRefOnly(),
			'siteLinkChanges' => $this->getSiteLinkChanges(),
			'otherChanges' => $this->hasOtherChanges(),
		];
	}

}
