( function () {
	'use strict';

	/**
	 * Escapes a string to be used in a regular expression.
	 *
	 * @ignore
	 *
	 * @param {string} value
	 * @return {string}
	 */
	function escapeRegex( value ) {
		return value.replace( /[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&' );
	}

	/**
	 * "Highlights" matching characters in a string using HTML.
	 *
	 *     @example
	 *     var highlighted = highlightSubstring( 'abc', 'abcdef' );
	 *     // highlighted === '<span class="highlighted">abc</span>def';
	 *
	 * @license GNU GPL v2+
	 * @author H. Snater < mediawiki@snater.com >
	 *
	 * @param {string} substring
	 * @param {string} string
	 * @param {Object} [options]
	 * @param {boolean} [options.caseSensitive=false]
	 * @param {boolean} [options.withinString=false]
	 *        Whether to highlight characters within the string, in contrast to at the beginning only.
	 * @param {string} [options.wrapperNodeName='span']
	 * @param {string} [options.wrapperNodeClass='highlight']
	 * @return {string}
	 */
	var highlightSubstring = function ( substring, string, options ) {
		if ( substring === '' || string === '' ) {
			return string;
		}

		options = options || {};
		options.caseSensitive = !!options.caseSensitive;
		options.withinString = !!options.withinString;
		options.wrapperNodeName = options.wrapperNodeName || 'span';
		options.wrapperNodeClass = options.wrapperNodeClass || 'highlight';

		var escapedSubstring = escapeRegex( substring );

		var regExpString = options.withinString
			? '((?:(?!' + escapedSubstring + ').)*?)(' + escapedSubstring + ')(.*)'
			: '^()(' + escapedSubstring + ')(.*)$';

		var indexOfSubstring = string.indexOf( substring );

		if ( options.caseSensitive
		&& (
			!options.withinString && indexOfSubstring !== 0
			|| options.withinString && indexOfSubstring === -1
		)
		) {
			return string;
		} else if ( !options.caseSensitive ) {
			var lowerCaseString = string.toLowerCase(),
				lowerCaseSubstring = substring.toLowerCase(),
				caseInsensitiveIndexOfSubstring = lowerCaseString.indexOf( lowerCaseSubstring );

			if ( !options.withinString && caseInsensitiveIndexOfSubstring !== 0
			|| options.withinString && caseInsensitiveIndexOfSubstring === -1
			) {
				return string;
			}
		}

		var matches = string.match(
			new RegExp( regExpString, options.caseSensitive ? '' : 'i' )
		);

		if ( matches ) {
			var wrapped = document.createElement( options.wrapperNodeName ),
				container = document.createElement( 'div' );

			wrapped.appendChild( document.createTextNode( matches[ 2 ] ) );

			if ( options.wrapperNodeClass ) {
				wrapped.setAttribute( 'class', options.wrapperNodeClass );
			}

			container.appendChild( wrapped );

			string = matches[ 1 ] + container.innerHTML + matches[ 3 ];
		}

		return string;
	};

	module.exports = highlightSubstring;

}() );
