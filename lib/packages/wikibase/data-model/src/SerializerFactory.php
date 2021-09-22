<?php

namespace Wikibase\DataModel;

use Wikibase\DataModel\Serializers\SerializerFactory as SerializerFactoryAlias;
use function class_alias;

class_alias(
	SerializerFactoryAlias::class,
	  __NAMESPACE__ . '\SerializerFactory'
);
