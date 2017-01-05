<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\ChangeOp\ChangeOpException;

/**
 * Interface for services that can construct a ChangeOp (or a series of changes wrapped in a
 * ChangeOps object) from a JSON style array structure describing changes to an entity.
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface ChangeOpDeserializer {

	/**
	 * @param array[] $changeRequest An array structure describing a changed entity (or changes to
	 *  an entity). The array structure is mostly compatible with an actual entity serialization,
	 *  but may contain additional array keys like "remove" or "add", for example:
	 *  [ 'label' => [ 'zh' => [ 'remove' ], 'de' => [ 'value' => 'Foo' ] ] ]
	 *
	 * @throws ChangeOpException when the provided array is invalid.
	 * @return ChangeOp|null Returns null if there is no relevant change in the provided
	 *  serialization.
	 */
	public function createEntityChangeOp( array $changeRequest );

}
