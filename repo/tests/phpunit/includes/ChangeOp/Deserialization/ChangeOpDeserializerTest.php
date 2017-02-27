<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

interface ChangeOpDeserializerTest {

	/**
	 * @return ChangeOpDeserializer
	 */
	public function getChangeOpDeserializer();

}
