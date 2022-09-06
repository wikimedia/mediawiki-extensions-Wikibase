<?php

namespace Wikibase\Repo\SeaHorse;

class SeaHorseSerializer implements \Serializers\DispatchableSerializer {

	public function isSerializerFor( $object ) {
		return $object instanceof SeaHorse;

	}

	public function serialize( $object ) {
		return [
			'content' => $object->getContent(),
		];
	}

}
