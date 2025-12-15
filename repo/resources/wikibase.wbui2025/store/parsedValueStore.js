const { defineStore } = require( 'pinia' );
const { reactive } = require( 'vue' );
const { parseValue } = require( '../api/editEntity.js' );

const generateParsedValueCacheKey = ( value, parseOptions ) => value +
	( parseOptions ? JSON.stringify( parseOptions ) : 'default' );

const useParsedValueStore = defineStore( 'parsedValue', {
	state: () => ( {
		parsedValuesPerProperty: new Map()
	} ),
	actions: {
		/**
		 * Request that the given input for the given property ID should be parsed,
		 * and return the parsed value asynchronously.
		 * Parsed values are cached, so the returned promise might already be resolved.
		 *
		 * @param {string} propertyId
		 * @param {string} value
		 * @param {Object} parseOptions
		 * @returns {Promise<object|null>} A promise that will resolve to the parsed value
		 * (a data value object with "type" and "value" keys), or null if it could not be parsed.
		 */
		getParsedValue( propertyId, value, parseOptions ) {
			let parsedValues = this.parsedValuesPerProperty.get( propertyId );
			if ( parsedValues === undefined ) {
				parsedValues = new Map();
				this.parsedValuesPerProperty.set( propertyId, parsedValues );
			}
			const parsedValueCacheKey = generateParsedValueCacheKey( value, parseOptions );
			let parsedValue = parsedValues.get( parsedValueCacheKey );
			if ( parsedValue === undefined ) {
				parsedValue = reactive( {
					promise: parseValue( value, parseOptions ).then( ( parsed ) => {
						parsedValue.resolved = parsed;
						return parsed;
					} ),
					resolved: undefined
				} );
				parsedValues.set( parsedValueCacheKey, parsedValue );
			}
			return parsedValue.promise;
		},
		/**
		 * Prime the parsedValue cache with a value.
		 * We only do this in the case of a plain string value.
		 *
		 * @param {string} propertyId
		 * @param {Object} dataValue
		 */
		preloadParsedValue( propertyId, dataValue ) {
			let parsedValues = this.parsedValuesPerProperty.get( propertyId );
			if ( parsedValues === undefined ) {
				parsedValues = new Map();
				this.parsedValuesPerProperty.set( propertyId, parsedValues );
			}
			const value = dataValue.value;
			// We need to set the parser options here in the same way they are set
			// in the StringValueStrategy so that we warm the correct cache entry
			const parsedValueCacheKey = generateParsedValueCacheKey( value, {
				property: propertyId
			} );
			if ( parsedValues.has( parsedValueCacheKey ) ) {
				return;
			}
			const parsedValue = {
				promise: Promise.resolve( dataValue ),
				resolved: dataValue
			};
			parsedValues.set( parsedValueCacheKey, parsedValue );
		},
		/**
		 * Add parsed values from the given statements (including their qualifiers and references).
		 *
		 * @param {Object} statements
		 */
		populateWithStatements( statements ) {
			const processSnak = ( snak ) => {
				if ( snak.snaktype !== 'value' ) {
					return;
				}
				if ( snak.datatype !== 'string' ) {
					return;
				}
				this.preloadParsedValue( snak.property, snak.datavalue );
			};
			for ( const [ , statementList ] of Object.entries( statements ) ) {
				for ( const statement of statementList ) {
					processSnak( statement.mainsnak );
					for ( const [ , qualifierSnaks ] of Object.entries( statement.qualifiers || {} ) ) {
						qualifierSnaks.forEach( processSnak );
					}
					for ( const reference of statement.references || [] ) {
						for ( const [ , referenceSnaks ] of Object.entries( reference.snaks ) ) {
							referenceSnaks.forEach( processSnak );
						}
					}
				}
			}
		},
		/**
		 * Get the parsed value for the given property ID and input,
		 * if it has already been parsed.
		 *
		 * @param {string} propertyId
		 * @param {string} value
		 * @param {Object} parseOptions
		 * @return {object|null|undefined} The parsed value (a data value object
		 * with "type" and "value" keys), null if it could not be parsed,
		 * or undefined if the parse was not yet requested or did not finish yet.
		 */
		peekParsedValue( propertyId, value, parseOptions ) {
			const parsedValueCacheKey = generateParsedValueCacheKey( value, parseOptions );
			const parsedValues = this.parsedValuesPerProperty.get( propertyId );
			if ( parsedValues === undefined ) {
				return undefined;
			}
			const parsedValue = parsedValues.get( parsedValueCacheKey );
			if ( parsedValue === undefined ) {
				return undefined;
			}
			return parsedValue.resolved;
		}
	}
} );

module.exports = {
	useParsedValueStore
};
