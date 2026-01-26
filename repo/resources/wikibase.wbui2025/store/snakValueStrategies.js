const { useParsedValueStore } = require( './parsedValueStore.js' );
const { renderSnakValueText } = require( '../api/editEntity.js' );
const {
	transformSearchResults,
	transformEntitySearchResults,
	searchCommonsMedia,
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
			this.getValueToParse(),
			this.getParseOptions()
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

	isEntityDatatype() {
		return false;
	}

	triggerParse( newValue ) {
		this.getParsedValue( newValue );
	}
}

class LookupStringDatatypeStrategy extends StringValueStrategy {
	getEditableSnakComponent() {
		return 'Wbui2025EditableLookupSnakValue';
	}
}

class QuantityValueStrategy extends StringValueStrategy {
	getParseOptions() {
		const options = {
			property: this.editSnakStore.property
		};
		if ( this.editSnakStore.unitconcepturi ) {
			options.options = JSON.stringify( {
				unit: this.editSnakStore.unitconcepturi
			} );
		}
		return options;
	}

	async renderValueForTextInput( valueObject ) {
		if ( !valueObject.value.amount ) {
			return '';
		}
		const valueWithoutUnits = Object.assign(
			{}, valueObject,
			{
				value: Object.assign(
					{}, valueObject.value,
					{ unit: '1' }
				)
			}
		);
		return renderSnakValueText( valueWithoutUnits );
	}

	getEditableSnakComponent() {
		return 'Wbui2025EditableQuantitySnakValue';
	}
}

class TimeValueStrategy extends StringValueStrategy {
	async renderValueForTextInput( valueObject ) {
		return renderSnakValueText( valueObject );
	}

	getParseOptions() {
		const defaultOptions = super.getParseOptions();
		const extraOptions = {};
		if ( this.editSnakStore.precision !== undefined ) {
			extraOptions.precision = this.editSnakStore.precision;
		}
		if ( this.editSnakStore.calendar !== undefined ) {
			extraOptions.calendar = this.editSnakStore.calendar;
		}
		if ( Object.keys( extraOptions ).length === 0 ) {
			return defaultOptions;
		}
		return Object.assign( defaultOptions, { options: JSON.stringify( extraOptions ) } );
	}

	getEditableSnakComponent() {
		return 'Wbui2025EditableTimeSnakValue';
	}
}

class EntityValueStrategy extends LookupStringDatatypeStrategy {

	constructor( editSnakStore, parser ) {
		super( editSnakStore );
		this.parser = parser;
	}

	getSelectionValueForSavedValue( valueObject ) {
		return valueObject.value ? valueObject.value.id : null;
	}

	async renderValueForTextInput( valueObject ) {
		return renderSnakValueText( valueObject );
	}

	getValueToParse() {
		return this.editSnakStore.selectionvalue;
	}

	getParsedValue( newValue = undefined ) {
		// Ignore the new value - will have been provided by the textvalue watcher
		// in editableLookupSnakValue, but we want to parse the selected entity value
		// ( selectionvalue )
		return useParsedValueStore().getParsedValue(
			this.editSnakStore.property,
			this.getValueToParse(),
			this.getParseOptions()
		);
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

class CommonsMediaValueStrategy extends LookupStringDatatypeStrategy {
	constructor( editSnakStore ) {
		super( editSnakStore );
	}

	transformSearchResults( data ) {
		return super.transformSearchResults( data ).map( ( menuItem ) => Object.assign( {
			thumbnail: {
				// /wiki/Special:Filepath/${ file } with a redirect level or two already resolved
				url: `https://commons.wikimedia.org/w/index.php?title=Special:Redirect/file/${ encodeURIComponent( menuItem.value.replace( / /g, '_' ) ) }&width=40`
			},
			showThumbnail: true
		}, menuItem ) );
	}
}

snakValueStrategyFactory.registerStrategyForDatatype(
	'wikibase-item',
	( store ) => new ItemValueStrategy( store ),
	( searchTerm, offset ) => searchForEntities( searchTerm, 'item', offset )
);
snakValueStrategyFactory.registerStrategyForDatatype(
	'wikibase-property',
	( store ) => new PropertyValueStrategy( store ),
	( searchTerm, offset ) => searchForEntities( searchTerm, 'property', offset )
);
snakValueStrategyFactory.registerStrategyForDatatype( 'quantity', ( store ) => new QuantityValueStrategy( store ) );
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
snakValueStrategyFactory.registerStrategyForDatatype( 'url', ( store ) => new StringValueStrategy( store ) );
snakValueStrategyFactory.registerStrategyForDatatype( 'commonsMedia',
	( store ) => new CommonsMediaValueStrategy( store ),
	( searchTerm, offset ) => searchCommonsMedia( searchTerm, offset )
);

module.exports = {
	EntityValueStrategy,
	StringValueStrategy
};
