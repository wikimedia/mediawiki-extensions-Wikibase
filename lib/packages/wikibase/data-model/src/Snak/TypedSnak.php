<?php

namespace Wikibase\DataModel\Snak;

/**
 * @since 0.7
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
class TypedSnak {

	/**
	 * @var Snak
	 */
	private $snak;

	/**
	 * @var string
	 */
	private $dataTypeId;

	/**
	 * @param Snak $snak
	 * @param string $dataTypeId
	 */
	public function __construct( Snak $snak, $dataTypeId ) {
		$this->snak = $snak;
		$this->dataTypeId = $dataTypeId;
	}

	/**
	 * @return string
	 */
	public function getDataTypeId() {
		return $this->dataTypeId;
	}

	/**
	 * @return Snak
	 */
	public function getSnak() {
		return $this->snak;
	}

}
