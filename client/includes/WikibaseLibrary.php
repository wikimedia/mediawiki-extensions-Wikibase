<?php
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {
    protected static $moduleName = 'mw.wikibase';
    public function register() {
        $lib = array( 'getEntity' => array( $this, 'getEntity' ) );
        $this->getEngine()->registerInterface( 'mw.wikibase.lua', $lib, array() );
    }

	public function getEntity( $entity ) {
		$this->checkType( 'getEntity', 1, $entity, 'string' );
        $content = \Wikibase\EntityContentFactory::singleton()->newFromEntity( $entity );
		return array( $content->getEntity() );
    }
}
