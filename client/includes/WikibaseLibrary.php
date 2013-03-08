<?php
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {
	function register() {
        $this->getEngine()->registerInterface( 'mw.wikibase.lua', 
            array( 'getEntity' => $this->getEntity ),
            array()
        );
	}

	public function getEntity( $entity ) {
		$this->checkType( 'getEntity', 1, $entity, 'string' );
        $content = \Wikibase\EntityContentFactory::singleton()->newFromEntity( $entity );
		return array( $content->getEntity() );
    }
}
