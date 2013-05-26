/**
 * Coordinate object
 *
 * @since 0.1
 * @file
 * @ingroup coordinate.js
 * @licence GNU GPL v2+
 *
 * @author Denny Vrandečić
 *
 * @dependency coordinate
 * @dependency coordinate.parser
 */
coordinate.Coordinate = ( function( coordinate, coordinateParser ) {
	'use strict';

	var Coordinate = function Coordinate( inputtext, inputprecision ) {
		var result = [0, 0, 0];

		this.getInputtext = function() { return inputtext; };

		try {
			result = coordinateParser.parse( inputtext );
		} catch ( err ) {
			result = [0, 0, 0];
			this.error = err.toString();
		}

		if ( Math.abs( result[0] ) > 90 || Math.abs( result[1] ) > 180 ) {
			result = [0, 0, 0];
		}

		var latitude = result[0];
		var longitude = result[1];
		this.latitudeInternal = function() { return latitude; };
		this.longitudeInternal = function() { return longitude; };
	
		var precision = ( inputprecision === undefined ) ? result[2] : inputprecision;
		this.precisionInternal = function() { return precision; };
		this.precisionText = function() { return coordinate.precisionText( precision ); };
		this.precisionTextEarth = function() { return coordinate.precisionTextEarth( precision ); };

		this.increasePrecision = function() {
			precision = coordinate.increasePrecision( precision );
			return precision;
		};
		this.decreasePrecision = function() {
			precision = coordinate.decreasePrecision( precision );
			return precision;
		};

		this.northsouth = function() {
			return ( latitude < 0 ) ? coordinate.settings.south : coordinate.settings.north;
		};
		this.eastwest = function() {
			return ( longitude < 0 ) ? coordinate.settings.west : coordinate.settings.east;
		};

		this.latitudeDegree = function() { return coordinate.toDegree( latitude, precision ); };
		this.longitudeDegree = function() { return coordinate.toDegree( longitude, precision ); };
		this.latitudeDecimal = function() { return coordinate.toDecimal( latitude, precision ); };
		this.longitudeDecimal = function() { return coordinate.toDecimal( longitude, precision ); };
		this.degreeText = function() { return coordinate.degreeText( latitude, longitude, precision ); };
		this.decimalText = function() { return coordinate.decimalText( latitude, longitude, precision ); };
	};

	return Coordinate; // expose coordinate.Coordinate

}( coordinate, coordinate.parser ) );
