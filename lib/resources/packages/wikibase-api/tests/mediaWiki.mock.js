// eslint-disable-next-line no-global-assign, no-implicit-globals, no-native-reassign
mediaWiki = {
	msg: function ( key ) {
		return '<' + key + '>';
	},
	html: {
		escape: function ( s ) {
			return s;
		}
	}
};
