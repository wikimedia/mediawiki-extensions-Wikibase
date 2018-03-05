<?php

namespace Wikibase\Repo\ChangeOp;

use Wikibase\Repo\ChangeOp\Deserialization\ChangeOpDeserializationException;

/**
 * Interface for services that can construct a ChangeOp from a JSON style array structure describing
 * changes to an entity.
 *
 * Implementations are encouraged to provide a detailed documentation of the serialization format
 * they are supporting in @see docs/change-op-serializations.wiki. The format must follow the
 * @see https://www.wikidata.org/wiki/Wikidata:Stable_Interface_Policy
 *
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 * @author Thiemo Kreuz
 */
interface ChangeOpDeserializer {

	/**
	 * @param array[] $changeRequest An array structure describing a changed entity (or changes to
	 *  an entity). The array structure is mostly compatible with an actual entity serialization,
	 *  but may contain additional array keys like "remove" or "add", for example:
	 *  [ 'label' => [ 'zh' => [ 'remove' ], 'de' => [ 'value' => 'Foo' ] ] ]
	 *
	 * @throws ChangeOpDeserializationException when the provided array is invalid.
	 * @return ChangeOp
	 *
	 * @see NullChangeOp If no change needs to be applied
	 * @see ChangeOps If series of changes needs to be applied
	 */
	public function createEntityChangeOp( array $changeRequest );

}
