<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Interface for services that can construct ChangeOp's from a JSON style array structure describing
 * changes to an entity.
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
	 * @return ChangeOp|null Returns null if the service was not able to find a relevant change in
	 *  the provided serialization.
	 */
	public function createEntityChangeOp( array $changeRequest );

}
