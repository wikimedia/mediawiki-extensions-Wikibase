/**
 * @author H. Snater < mediawiki@snater.com >
 * @author Daniel Werner < daniel.werner@wikimedia.de >
 */
( function( $, vv, GlobeCoordinate, Formatter ) {
	'use strict';

	var PARENT = vv.experts.StringValue;

	/**
	 * Valueview expert handling input of globe coordinate values.
	 *
	 * @since 0.1
	 *
	 * @constructor
	 * @extends jQuery.valueview.Expert
	 */
	vv.experts.GlobeCoordinateInput = vv.expert( 'GlobeCoordinateInput', PARENT, function() {
		PARENT.apply( this, arguments );

		var self = this;

		this.preview = new vv.ExpertExtender.Preview( function() {
			return self.viewState().getFormattedValue();
		} );


		var precisionMsgKey = 'valueview-expert-globecoordinateinput-precision';
		var $precisionContainer = $( '<div/>' )
			.addClass( this.uiBaseClass + '-precisioncontainer' )
			.append( $( '<div/>' ).text( this._messageProvider.getMessage( precisionMsgKey ) ) );

		this.precisionRotator = new vv.ExpertExtender.Listrotator(
			this.uiBaseClass + '-precision',
			getPrecisionValues(),
			function( newPrecisionLevel ) {
				self._viewNotifier.notify( 'change' );
			},
			function(){
				var value = self.viewState().value();
				return value && roundPrecision( value.getValue().getPrecision() );
			}
		);

		var inputExtender = new vv.ExpertExtender(
			this.$input,
			[
				this.preview,
				new vv.ExpertExtender.Toggler(
					this._messageProvider,
					$precisionContainer
				),
				new vv.ExpertExtender.Container(
					$precisionContainer,
					this.precisionRotator
				)
			]
		);

		this.addExtension( inputExtender );
	}, {

		/**
		 * @type {jQuery.valueview.ExpertExtender.Preview}
		 */
		preview: null,

		/**
		 * @type {jQuery.valueview.ExpertExtender.Listrotator}
		 */
		precisionRotator: null,

		/**
		 * Options.
		 * @type {Object}
		 */
		_options: {
			messages: {
				'valueview-expert-globecoordinateinput-precision': 'Precision'
			}
		},

		/**
		 * @see jQuery.valueview.Expert.valueCharacteristics
		 */
		valueCharacteristics: function() {
			if( !this.precisionRotator ) { // happens when called statically
				return {};
			}

			var options = {},
				precision = getPrecisionSetting( this.precisionRotator.getValue() );

			if( precision !== null ) {
				options.precision = precision;
			}

			return options;
		},

		/**
		 * @see jQuery.valueview.Expert.destroy
		 */
		destroy: function() {
			this.precisionRotator = null;
			this.preview = null;
			PARENT.prototype.destroy.call( this );
		}
	} );

	/**
	 * Rounds a given precision for being able to use it as internal "constant".
	 *
	 * TODO: Calculated numbers used for the precision (e.g. 1/60) may result in different values
	 *  in front- and back-end. Either use dedicated float numbers in front- and back-end or
	 *  integrate the rounding in GlobeCoordinate.
	 *
	 * @since 0.1
	 *
	 * @param {number} precision
	 * @return {number}
	 */
	function roundPrecision( precision ) {
		var precisions = GlobeCoordinate.PRECISIONS,
			highestPrecision = precisions[precisions.length - 1],
			multiplier = 1;

		// To make sure that not too much digits are cut off for the "constant" precisions to be
		// distinctive, we round with a multiplier that rounds the most precise precision to a
		// number greater than 1:
		while( highestPrecision * multiplier < 1 ) {
			multiplier *= 10;
		}

		return Math.round( precision * multiplier ) / multiplier;
	}

	/**
	 * Returns the original precision level for a rounded precision.
	 *
	 * @since 0.1
	 *
	 * @param {number} roundedPrecision
	 * @return {number|null}
	 */
	function getPrecisionSetting( roundedPrecision ) {
		var rounded,
			actualPrecision = null;

		$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
			rounded = roundPrecision( precision );
			if( rounded === roundedPrecision ) {
				actualPrecision = precision;
				return false;
			}
		} );

		return actualPrecision;
	}

	function getPrecisionValues() {
		var precisionValues = [];
		$.each( GlobeCoordinate.PRECISIONS, function( i, precision ) {
			var label = Formatter.PRECISIONTEXT( precision );
			precisionValues.unshift( {
				value: roundPrecision( precision ),
				label: label
			} );
		} );
		return precisionValues;
	}

}( jQuery, jQuery.valueview, globeCoordinate.GlobeCoordinate, globeCoordinate.Formatter ) );
