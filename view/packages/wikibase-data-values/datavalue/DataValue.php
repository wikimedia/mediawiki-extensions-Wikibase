<?php

namespace DataValue {

interface Hashable {

	public function getHash();

}

interface Comparable {

	public function equals( $dataValue );

}

/**
 * Interface for objects that represent a single data value.
 *
 * @since 0.1
 *
 * @file
 * @ingroup DataValue
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 */
interface DataValue extends Hashable, Comparable, \Serializable {

	public function getType();

	public function getSortKey();

}

}

