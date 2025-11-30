class DefaultStrategy {

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
		return { };
	}

	buildDataValue() {
		return this.editSnakStore.textvalue;
	}

	peekDataValue() {
		return this.editSnakStore.textvalue;
	}

	getParsedValue( newValue = undefined ) {
		return {
			value: this.editSnakStore.textvalue,
			type: 'string'
		};
	}

	isEntityDatatype() {
		return false;
	}

	getEditableSnakComponent() {
		return 'Wbui2025EditableStringSnakValue';
	}

	triggerParse( newValue ) {
		// Nothing to parse
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
			mw.log.warn( "Unsupported value type '" + snakStore.datatype + "'. Falling back to DefaultStrategy" );
			return new DefaultStrategy( snakStore );
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

module.exports = {
	snakValueStrategyFactory,
	DefaultStrategy
};
