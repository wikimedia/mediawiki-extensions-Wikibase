/**
 * @licence GNU GPL v2+
 * @author H. Snater < mediawiki@snater.com >
 */

jQuery.valueview = jQuery.valueview || {};

( function( $ ) {
	'use strict';

	/**
	 * Factory managing jQuery.valueview.Expert instances
	 * @constructor
	 * @since 0.1
	 *
	 * @param {Function} [DefaultExpert] Constructor of a default expert that shall be returned when
	 *        no expert is registered for a specific purpose.
	 */
	var SELF = $.valueview.ExpertFactory = function ValueviewExpertFactory( DefaultExpert ) {
		this._DefaultExpert = DefaultExpert || null;
		this._expertsForDataValueTypes = {};
		this._expertsForDataTypes = {};
	};

	$.extend( SELF.prototype, {
		/**
		 * Default expert constructor to be returned when no expert is registered for a specific
		 * purpose.
		 * @type {Function|null}
		 */
		_DefaultExpert: null,

		/**
		 * @type {Object}
		 */
		_expertsForDataValueTypes: null,

		/**
		 * @type {Object}
		 */
		_expertsForDataTypes: null,

		/**
		 * Registers a valueview expert for displaying data values suitable for a certain data type.
		 * @since 0.1
		 *
		 * @param {Function} Expert
		 * @param {string} dataTypeId
		 *
		 * @throws {Error} if no data type id is specified.
		 * @throws {Error} if an expert for the specified data type id is registered already.
		 */
		registerDataTypeExpert: function( Expert, dataTypeId ) {
			assertIsExpertConstructor( Expert );

			if( dataTypeId === undefined ) {
				throw new Error( 'No proper data type id provided to register the expert for' );
			}

			if( this._expertsForDataTypes[dataTypeId] ) {
				throw new Error( 'Expert for data type "' + dataTypeId + '" is registered already' );
			}

			this._expertsForDataTypes[dataTypeId] = Expert;
		},

		/**
		 * Registers a valueview expert for displaying values of a certain data value type.
		 * @since 0.1
		 *
		 * @param {Function} Expert
		 * @param {string} dataValueType
		 *
		 * @throws {Error} if no data value type is specified.
		 * @throws {Error} if an expert for the specified DataValue type is registered already.
		 */
		registerDataValueExpert: function( Expert, dataValueType ) {
			assertIsExpertConstructor( Expert );

			if( dataValueType === undefined ) {
				throw new Error( 'No proper data value type provided to register the expert for' );
			}

			if( this._expertsForDataValueTypes[dataValueType] ) {
				throw new Error( 'Expert for data value type "' + dataValueType + '" is registered '
					+ 'already' );
			}

			this._expertsForDataValueTypes[dataValueType] = Expert;
		},

		/**
		 * Returns the expert registered for a data type (if a data type expert is registered and
		 * a data type id is specified) or the expert registered for a data value type. If no expert
		 * is registered regarding the specified parameters, "null" is returned.
		 *
		 * @param {string} dataValueType
		 * @param {string} [dataTypeId]
		 * @return {Function|null}
		 *
		 * @throws {Error} if no proper parameters have been specified.
		 */
		getExpert: function( dataValueType, dataTypeId ) {
			var expert;

			if( typeof dataTypeId === 'string' ) {
				expert = this._expertsForDataTypes[dataTypeId];
			}

			if( !expert && typeof dataValueType === 'string' ) {
				expert = this._expertsForDataValueTypes[dataValueType];
			} else if( !expert ) {
				throw new Error( 'No sufficient purpose provided for choosing an expert' );
			}

			return expert || this._DefaultExpert;
		}
	} );

	/**
	 * @param {Function} Expert
	 * @throws {Error} if the provided argument is not a jQuery.valueview.Expert constructor.
	 */
	function assertIsExpertConstructor( Expert ) {
		if( !( $.isFunction( Expert ) && Expert.prototype instanceof $.valueview.Expert ) ) {
			throw new Error( 'Invalid jQuery.valueview.Expert constructor' );
		}
	}

}( jQuery ) );
