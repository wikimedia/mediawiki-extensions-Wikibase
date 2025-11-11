const { useParsedValueStore } = require( './parsedValueStore.js' );
const { renderSnakValueText } = require( '../api/editEntity.js' );
const {
	transformSearchResults,
	transformEntitySearchResults,
	searchForEntities,
	searchGeoShapes,
	searchTabularData
} = require( '../api/commons.js' );
const { snakValueStrategyFactory, DefaultStrategy } = require( './snakValueStrategyFactory.js' );

class StringValueStrategy extends DefaultStrategy {

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
	EntityValueStrategy
};
