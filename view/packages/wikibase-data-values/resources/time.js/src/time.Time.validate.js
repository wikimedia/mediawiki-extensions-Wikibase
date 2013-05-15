/**
 * time.Time.validate for validating structures passed to the time.Time constructor.
 *
 * @since 0.1
 * @file
 * @ingroup Time.js
 * @licence GNU GPL v2+
 *
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
time.Time.validate = ( function( Time ) {
	'use strict';

	/**
	 * Makes sure a given time structure is valid. If not, an Error will be thrown.
	 *
	 * @param {Object} definition
	 * @throws {Error}
	 */
	return function validateTimeDefinition( definition ) {
		validateFieldTypes( definition, {
			day: 'number',
			month: 'number',
			year: 'number',
			calendarname: 'string',
			precision: 'number'
		} );

		if( definition.year === undefined || isNaN( definition.year ) ) {
			throw new Error( '"year" has to be a number' );
		}

		if( definition.month > 12 || definition.month < 1 ) {
			throw new Error( '"month" must not be lower than 1 (January) or higher than 12 ' +
				'(December). "' + definition.month + '" is not a valid month number.'  );
		}

		if( definition.day < 1 ) {
			throw new Error( '"day" must not be lower than 1' );
		}
		// TODO: Add check for last day of the month once we have one validator per calendar model.

		// TODO: remove the following check once we have one validator per calendar model:
		if( definition.calendarname !== Time.CALENDAR.GREGORIAN
			&& definition.calendarname !== Time.CALENDAR.JULIAN
		) {
			throw new Error( '"calendarname" is "' + definition.calendarname + '" but has to be "'
				+ Time.CALENDAR.GREGORIAN + '" or "' + Time.CALENDAR.JULIAN + '"' );
		}

		if( !Time.knowsPrecision( definition.precision ) ) {
			throw new Error( 'Unknown precision "' + definition.precision + '" given in "precision"' );
		}
	};

	/**
	 * Checks a definition for certain fields. If the field is available, an error will be thrown
	 * in case the field is not of the specified type.
	 *
	 * @param {{key: string, type: string}} fieldTypes
	 * @param {Object} definition
	 */
	function validateFieldTypes( fieldTypes, definition ) {
		var field, value, requiredType;

		for( field in definition ) {
			value = fieldTypes[ field ];
			requiredType = definition[ field ];

			if( !requiredType ) {
				throw new Error( 'Unknown field "' + field + '" found in structure' );
			}
			if( value !== undefined && typeof value !== requiredType ) {
				throw new Error( 'Field "' + field + '" has to be of type ' + requiredType );
			}
		}
	}

}( time.Time ) );
