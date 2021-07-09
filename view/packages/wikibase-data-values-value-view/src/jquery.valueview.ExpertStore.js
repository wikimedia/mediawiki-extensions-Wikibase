jQuery.valueview = jQuery.valueview || {};

( function () {
	'use strict';

	/**
	 * @ignore
	 *
	 * @param {Function} Expert
	 * @throws {Error} if the provided argument is not a `jQuery.valueview.Expert` constructor.
	 */
	function assertIsExpertConstructor( Expert ) {
		if ( !( typeof Expert === 'function' && Expert.prototype instanceof $.valueview.Expert ) ) {
			throw new Error( 'Invalid jQuery.valueview.Expert constructor' );
		}
	}

	/**
	 * Store managing `jQuery.valueview.Expert` instances.
	 *
	 * @class jQuery.valueview.ExpertStore
	 * @since 0.1
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @constructor
	 *
	 * @param {Function|null} [DefaultExpert=null]
	 *        Constructor of a default expert that shall be returned when no expert is registered
	 *        for a specific purpose.
	 */
	var SELF = $.valueview.ExpertStore = function ValueviewExpertStore( DefaultExpert ) {
		this._DefaultExpert = DefaultExpert || null;
		this._expertsForDataValueTypes = {};
		this._expertsForDataTypes = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Default `Expert` constructor to be returned when no `Expert` is registered for a specific
		 * purpose.
		 *
		 * @property {Function|null}
		 * @private
		 */
		_DefaultExpert: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_expertsForDataValueTypes: null,

		/**
		 * @property {Object}
		 * @private
		 */
		_expertsForDataTypes: null,

		/**
		 * Registers a `valueview` `Expert` for displaying data values suitable for a certain data
		 * type.
		 *
		 * @param {Function} Expert
		 * @param {string} dataTypeId
		 *
		 * @throws {Error} if no data type id is specified.
		 * @throws {Error} if an expert for the specified data type id is registered already.
		 */
		registerDataTypeExpert: function( Expert, dataTypeId ) {
			assertIsExpertConstructor( Expert );

			if ( dataTypeId === undefined ) {
				throw new Error( 'No proper data type id provided to register the expert for' );
			}

			if ( this._expertsForDataTypes[dataTypeId] ) {
				throw new Error( 'Expert for data type "' + dataTypeId + '" is registered already' );
			}

			this._expertsForDataTypes[dataTypeId] = Expert;
		},

		/**
		 * Registers a `valueview` `Expert` for displaying values of a certain data value type.
		 *
		 * @param {Function} Expert
		 * @param {string} dataValueType
		 *
		 * @throws {Error} if no data value type is specified.
		 * @throws {Error} if an expert for the specified DataValue type is registered already.
		 */
		registerDataValueExpert: function( Expert, dataValueType ) {
			assertIsExpertConstructor( Expert );

			if ( dataValueType === undefined ) {
				throw new Error( 'No proper data value type provided to register the expert for' );
			}

			if ( this._expertsForDataValueTypes[dataValueType] ) {
				throw new Error( 'Expert for data value type "' + dataValueType + '" is registered '
					+ 'already' );
			}

			this._expertsForDataValueTypes[dataValueType] = Expert;
		},

		/**
		 * Returns the `Expert` registered for a data type (if a data type `Expert` is registered
		 * and a data type id is specified) or the `Expert` registered for a data value type. If no
		 * `Expert` is registered regarding the specified parameters, `null` is returned.
		 *
		 * @param {string} dataValueType
		 * @param {string} [dataTypeId]
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper parameters have been specified.
		 */
		getExpert: function( dataValueType, dataTypeId ) {
			var expert;

			if ( typeof dataTypeId === 'string' ) {
				expert = this._expertsForDataTypes[dataTypeId];
			}

			if ( !expert && typeof dataValueType === 'string' ) {
				expert = this._expertsForDataValueTypes[dataValueType];
			} else if ( !expert ) {
				throw new Error( 'No sufficient purpose provided for choosing an expert' );
			}

			return expert || this._DefaultExpert;
		}
	} );

}() );
