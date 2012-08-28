<?php

// Mock class to facilitate implementing snak code without having the DataValues extension yet.

namespace DataValue {

	interface DataValue extends \Wikibase\Immutable {

	}

	class DataValueObject implements DataValue {

	}

}