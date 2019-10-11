// eslint-disable-next-line no-global-assign, no-implicit-globals
mw = {
	msg: function ( key ) {
		return '<' + key + '>';
	},
	html: {
		escape: function ( s ) {
			return s;
		}
	},
	user: {
		isAnon: function () {
			return ( !mw._mockUser );
		},
		getName: function () {
			return mw._mockUser;
		}
	},
	_mockUser: null
};
