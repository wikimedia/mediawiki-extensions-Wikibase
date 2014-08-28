<?php

namespace Wikibase\DataModel\Entity\Diff;
use Wikibase\DataModel\Entity\EntityDocument;

/**
 * @since 1.0
 *
 * @licence GNU GPL v2+
 * @author Jeroen De Dauw < jeroendedauw@gmail.com >
 * @author Christoph Fischer < christoph.fischer@wikimedia.de >
 */
class EntityPatcher {

    public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {

        return $entity;
    }

}