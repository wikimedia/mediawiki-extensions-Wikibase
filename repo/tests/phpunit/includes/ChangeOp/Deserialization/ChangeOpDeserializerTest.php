<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

interface ChangeOpDeserializerTest {

	/**
	 * @return ChangeOpDeserializer
	 */
	public function getChangeOpDeserializer();

	/**
	 * @return EntityDocument
	 */
	public function getEntity();

}
