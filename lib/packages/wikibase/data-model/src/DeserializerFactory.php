<?php

namespace Wikibase\DataModel;

use Wikibase\DataModel\Deserializers\DeserializerFactory as DeserializerFactoryAlias;
use function class_alias;

class_alias(
	DeserializerFactoryAlias::class,
	  __NAMESPACE__ . '\DeserializerFactory'
);
