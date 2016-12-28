<?php

namespace Wikibase\Repo\ChangeOp;

/**
 * Deserializer for EditEntity API
 *
 * @license GPL-2.0+
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
interface EditEntityApiChangeOpDeserializer {

        /**
	 * @param array $data an array of data to apply. For example:
	 *        [ 'label' => [ 'zh' => [ 'remove' ], 'de' => [ 'value' => 'Foo' ] ] ]
	 * @param ChangeOps $changeOps ChangeOps to apploy
	 * @param EntityDocument $entity entity to run validations. For example if it
	 *        implements LabelsProvider when applying labels.
         * @return ChangeOps
         */
        public function deserialize( array $data, ChangeOps $changeOps, EntityDocument $entity );

}

