// Utility functions for use within Vue templates.
// For SSR elements, duplicate definitions must exist in
// WMDE\VueJsTemplating\App::methods
module.exports = exports = {
	concat: ( ...args ) => args.join()
};
