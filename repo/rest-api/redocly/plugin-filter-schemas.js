'use strict';

module.exports = function () {
	return {
		id: 'filterSchemas',
		decorators: {
			oas3: {
				filterSchemas: function () {
					let originalSchemas;

					return {
						Components: {
							leave( components ) {
								for ( const key in components.schemas || {} ) {
									if ( originalSchemas[ key ] === undefined ) {
										delete components.schemas[ key ];
									}
								}
							},
							NamedSchemas: {
								enter( schemas ) {
									originalSchemas = schemas;
								}
							}
						}
					};
				}
			}
		}
	};
};
