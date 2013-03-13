<?php
class Scribunto_LuaWikibaseLibrary extends Scribunto_LuaLibraryBase {
	protected static $moduleName = 'wikibase';
	public function register() {
		$lib = array( 'getEntity' => array( $this, 'getEntity' ) );
		$this->getEngine()->registerInterface( dirname( __FILE__ ) . '/../resources/' . 'mw.wikibase.lua', $lib, array() );
	}

	public function getEntity( $prefixedEntityId ) {
		$this->checkType( 'getEntity', 1, $prefixedEntityId, 'string' );
		$entityObject = Wikibase\ClientStoreFactory::getStore()->newEntityLookup()->getEntity( Wikibase\EntityId::newFromPrefixedId( $prefixedEntityId ) );

		$serializerFactory = new \Wikibase\Lib\Serializers\SerializerFactory();
		$serializer = $serializerFactory->newSerializerForObject( $entityObject );

		$opt = new \Wikibase\Lib\Serializers\EntitySerializationOptions();
		$serializer->setOptions( $opt );

		$entityArr = $serializer->getSerialized( $entityObject );
		return array( $entityArr );
	}
}
