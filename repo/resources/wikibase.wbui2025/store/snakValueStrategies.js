const { useParsedValueStore } = require( './parsedValueStore.js' );
const { renderSnakValueText } = require( '../api/editEntity.js' );
const {
	transformSearchResults,
	transformEntitySearchResults,
	searchForEntities,
	searchGeoShapes,
	searchTabularData
} = require( '../api/commons.js' );

class StringValueStrategy {

	constructor( editSnakStore ) {
		this.editSnakStore = editSnakStore;
	}

	getStore() {
		return this.editSnakStore;
	}

	async renderValueToText( valueObject ) {
		return valueObject.value;
	}

	getSelectionValueForSavedValue( valueObject ) {
		return valueObject.value;
	}

	getValueToParse() {
		return this.editSnakStore.textvalue;
	}

	getParseOptions() {
		return {
			property: this.editSnakStore.property
		};
	}

	buildDataValue() {
		return this.getParsedValue();
	}

	peekDataValue() {
		const parsedValueStore = useParsedValueStore();
		return parsedValueStore.peekParsedValue(
			this.editSnakStore.property,
			this.getValueToParse()
		);
	}

	getParsedValue( newValue = undefined ) {
		const parsedValueStore = useParsedValueStore();
		if ( newValue === undefined ) {
			newValue = this.getValueToParse();
		}
		return parsedValueStore.getParsedValue(
			this.editSnakStore.property,
			newValue,
			this.getParseOptions()
		);
	}

	transformSearchResults( data ) {
		if ( !data.query || !data.query.search || data.query.search.length === 0 ) {
			return [];
		}
		return transformSearchResults( data.query.search );
	}

	isLookupDatatype() {
		return false;
	}

	isEntityDatatype() {
		return false;
	}
}

class LookupStringDatatypeStrategy extends StringValueStrategy {
	isLookupDatatype() {
		return true;
	}
}

class TimeValueStrategy extends StringValueStrategy {
	async renderValueToText( valueObject ) {
		return valueObject.value.time;
	}
}

class EntityValueStrategy extends LookupStringDatatypeStrategy {

	constructor( editSnakStore, parser ) {
		super( editSnakStore );
		this.parser = parser;
	}

	getSelectionValueForSavedValue( valueObject ) {
		return valueObject.value.id;
	}

	async renderValueToText( valueObject ) {
		return renderSnakValueText( valueObject );
	}

	getValueToParse() {
		return this.editSnakStore.selectionvalue;
	}

	getParseOptions() {
		return {
			parser: this.parser
		};
	}

	transformSearchResults( data ) {
		if ( data.length === 0 ) {
			return [];
		}
		return transformEntitySearchResults( data );
	}

	isEntityDatatype() {
		return true;
	}
}

class ItemValueStrategy extends EntityValueStrategy {
	constructor( editSnakStore ) {
		super( editSnakStore, 'wikibase-item' );
	}
}

class PropertyValueStrategy extends EntityValueStrategy {
	constructor( editSnakStore ) {
		super( editSnakStore, 'wikibase-property' );
	}
}

class SnakValueStrategyFactory {
	constructor() {
		this.strategiesByDatatype = {};
		this.searchesByDatatype = {};
	}

	registerStrategyForDatatype( datatype, strategyKlass, searchByDatatype = null ) {
		this.strategiesByDatatype[ datatype ] = strategyKlass;
		if ( searchByDatatype ) {
			this.searchesByDatatype[ datatype ] = searchByDatatype;
		}
	}

	getStrategyForSnakStore( snakStore ) {
		if ( snakStore.datatype in this.strategiesByDatatype ) {
			return this.strategiesByDatatype[ snakStore.datatype ]( snakStore );
		} else {
			mw.log.warn( "Unsupported value type '" + snakStore.datatype + "'. Falling back to string" );
			return new StringValueStrategy( snakStore );
		}
	}

	searchByDatatype( datatype, searchTerm, offset = 0 ) {
		if ( !( datatype in this.searchesByDatatype ) ) {
			throw new Error( `Unsupported datatype for search: ${ datatype }` );
		}
		return this.searchesByDatatype[ datatype ]( searchTerm, offset );
	}
}

const snakValueStrategyFactory = new SnakValueStrategyFactory();
snakValueStrategyFactory.registerStrategyForDatatype(
	'wikibase-item',
	( store ) => new ItemValueStrategy( store ),
	( searchTerm, offset ) => searchForEntities( searchTerm, 'item' )
);
snakValueStrategyFactory.registerStrategyForDatatype(
	'wikibase-property',
	( store ) => new PropertyValueStrategy( store ),
	( searchTerm, offset ) => searchForEntities( searchTerm, 'property' )
);
snakValueStrategyFactory.registerStrategyForDatatype( 'time', ( store ) => new TimeValueStrategy( store ) );
snakValueStrategyFactory.registerStrategyForDatatype(
	'geo-shape',
	( store ) => new LookupStringDatatypeStrategy( store ),
	( searchTerm, offset ) => searchGeoShapes( searchTerm, offset )
);
snakValueStrategyFactory.registerStrategyForDatatype(
	'tabular-data',
	( store ) => new LookupStringDatatypeStrategy( store ),
	( searchTerm, offset ) => searchTabularData( searchTerm, offset )
);
snakValueStrategyFactory.registerStrategyForDatatype( 'string', ( store ) => new StringValueStrategy( store ) );

module.exports = {
	snakValueStrategyFactory
};
