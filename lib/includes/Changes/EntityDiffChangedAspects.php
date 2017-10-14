<?php

namespace Wikibase\Lib\Changes;

use MWException;
use Serializable;
use Wikimedia\Assert\Assert;

/**
 * @license GPL-2.0+
 * @author Marius Hoch
 */
class EntityDiffChangedAspects implements Serializable {

	/**
	 * Increases whenever the array format (self::toArray) changes
	 */
	const ARRAYFORMATVERSION = 0;

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
	 * Property id serialization from the statements that changed (added, removed or updated)
	 *
	 * @var string[]
	 */
	private $statementChanges;

	/**
	 * Map of site ids to bool. The bool indicates whether only the badge has changed (false)
	 * or the actual value of the sitelink changed (true).
	 *
	 * @var bool[]
	 */
	private $siteLinkChanges;

	/**
	 * Do we have changes that are not covered more specifically?
	 *
	 * @var bool
	 */
	private $otherChanges;

	/**
	 * @param string[] $labelChanges Language codes of the labels that changed (added, removed or updated)
	 * @param string[] $descriptionChanges Language codes of the descriptions that changed (added, removed or updated)
	 * @param string[] $statementChanges Property id serialization from the statements that changed (added, removed or updated)
	 * @param bool[] $siteLinkChanges Map of site ids to bool: only the badge has changed (false) or the actual sitelink changed (true)
	 * @param bool $otherChanges Do we have changes that are not covered more specifically?
	 */
	public function __construct(
		array $labelChanges,
		array $descriptionChanges,
		array $statementChanges,
		array $siteLinkChanges,
		$otherChanges
	) {
		Assert::parameterElementType( 'string', $labelChanges, '$labelChanges' );
		Assert::parameterElementType( 'string', $descriptionChanges, '$descriptionChanges' );
		Assert::parameterElementType( 'string', $statementChanges, '$statementChanges' );
		Assert::parameterElementType( 'string', array_keys( $siteLinkChanges ), 'array_keys( $siteLinkChanges )' );
		Assert::parameterElementType( 'boolean', $siteLinkChanges, '$siteLinkChanges' );
		Assert::parameterType( 'boolean', $otherChanges, '$otherChanges' );

		$this->labelChanges = $labelChanges;
		$this->descriptionChanges = $descriptionChanges;
		$this->statementChanges = $statementChanges;
		$this->siteLinkChanges = $siteLinkChanges;
		$this->otherChanges = $otherChanges;
	}

	/**
	 * @return string[]
	 */
	public function getLabelChanges() {
		return $this->labelChanges;
	}

	/**
	 * @return string[]
	 */
	public function getDescriptionChanges() {
		return $this->descriptionChanges;
	}

	/**
	 * @return string[]
	 */
	public function getStatementChanges() {
		return $this->statementChanges;
	}

	/**
	 * @return bool[]
	 */
	public function getSiteLinkChanges() {
		return $this->siteLinkChanges;
	}

	/**
	 * @return bool
	 */
	public function hasOtherChanges() {
		return $this->otherChanges;
	}

	/**
	 * @return string
	 */
	public function serialize() {
		return json_encode( $this->toArray() );
	}

	/**
	 * @return string
	 */
	public function unserialize( $serialized ) {
		$data = json_decode( $serialized );

		if ( $data->arrayFormatVersion !== 0 ) {
			throw new MWException( 'Unsupported format version ' . $data->arrayFormatVersion );
		}

		$this->labelChanges = $data->labelChanges;
		$this->descriptionChanges = $data->descriptionChanges;
		$this->statementChanges = $data->statementChanges;
		$this->siteLinkChanges = (array) $data->siteLinkChanges;
		$this->otherChanges = $data->otherChanges;
	}

	/**
	 * @return array[]
	 */
	public function toArray() {
		return [
			'arrayFormatVersion' => self::ARRAYFORMATVERSION,
			'labelChanges' => $this->getLabelChanges(),
			'descriptionChanges' => $this->getDescriptionChanges(),
			'statementChanges' => $this->getStatementChanges(),
			'siteLinkChanges' => $this->getSiteLinkChanges(),
			'otherChanges' => $this->hasOtherChanges(),
		];
	}

}
