const { defineStore } = require( 'pinia' );
const { parseValue } = require( '../api/editEntity.js' );

const useParsedValueStore = defineStore( 'parsedValue', {
	state: () => ( {
		parsedValuesPerProperty: new Map()
	} ),
	actions: {
		getParsedValue( propertyId, value ) {
			let parsedValues = this.parsedValuesPerProperty.get( propertyId );
			if ( parsedValues === undefined ) {
				parsedValues = new Map();
				this.parsedValuesPerProperty.set( propertyId, parsedValues );
			}
			let parsedValue = parsedValues.get( value );
			if ( parsedValue === undefined ) {
				parsedValue = {
					promise: parseValue( propertyId, value ).then( ( parsed ) => {
						parsedValue.resolved = parsed;
						return parsed;
					} ),
					resolved: undefined
				};
				parsedValues.set( value, parsedValue );
			}
			return parsedValue.promise;
		}
	}
} );

module.exports = {
	useParsedValueStore
};
