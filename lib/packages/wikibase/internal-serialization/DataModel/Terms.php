<?php

namespace Wikibase\DataModel\Term;

class Terms {

	private $labels;
	private $descriptions;
	private $aliases;

	public function __construct( LabelList $labels, DescriptionList $descriptions, AliasGroupList $aliases ) {
		$this->labels = $labels;
		$this->descriptions = $descriptions;
		$this->aliases = $aliases;
	}

	/**
	 * @return LabelList
	 */
	public function getLabels() {
		return $this->labels;
	}

	/**
	 * @return DescriptionList
	 */
	public function getDescriptions() {
		return $this->descriptions;
	}

	/**
	 * @return AliasGroupList
	 */
	public function getAliases() {
		return $this->aliases;
	}

}