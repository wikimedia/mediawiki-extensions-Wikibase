<?php

namespace Wikibase\Repo\Tests\ChangeOp\Deserialization;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\Repo\ChangeOp\ChangeOpDeserializer;

/**
 * Interface for test classes that reuse methods from other ChangeOpDeserializer tests.
 *
 * @group Wikibase
 *
 * @license GPL-2.0+
 */
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
