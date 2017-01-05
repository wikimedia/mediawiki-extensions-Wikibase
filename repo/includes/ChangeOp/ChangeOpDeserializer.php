<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\ChangeOp\ChangeOp;
use Wikibase\ChangeOp\ChangeOpException;

/**
 * Interface for services that can construct a ChangeOp from a JSON style array structure describing changes to an entity.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface ChangeOpDeserializer {

	/**
	 * @return string Top level key which this deserializer can process
	 */
	public function getKey();

	/**
	 * @param array $changeSubRequest An array structure describing a changed part of the entity (or changes to
	 *  an entity). The array structure is specific for each top level key, for example:
	 *  for top level key 'label' it might be something like `[ 'zh' => [ 'remove' ], 'de' => [ 'value' => 'Foo' ] ]`
	 *
	 * @throws ChangeOpException when the provided array is invalid.
	 * @return ChangeOp
	 *
	 * @see NullChangeOp If no change needs to be applied
	 * @see ChangeOps If series of changes needs to be applied
	 */
	public function createChangeOp( array $changeSubRequest );
}
