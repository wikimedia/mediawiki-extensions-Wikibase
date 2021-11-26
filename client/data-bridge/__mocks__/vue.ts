import vue from 'vue';

( vue as typeof vue & { createMwApp: Function } ).createMwApp = function ( componentOptions, propsData ) {
	const App: any = vue.extend( componentOptions );
	const finalOptions: any = {};

	if ( propsData ) {
		finalOptions.propsData = propsData;
	}

	// Wrap .use(), so we can redirect app.use( VuexStore )
	App.use = function ( plugin: any, ...params: unknown[] ): typeof App {
		if (
			typeof plugin !== 'function' && typeof plugin.install !== 'function' &&
			// eslint-disable-next-line no-underscore-dangle
			plugin._actions && plugin._mutations
		) {
			// This looks like a Vuex store, store it for later use.
			finalOptions.store = plugin;
			return this;
		}
		vue.use( plugin, ...params );
		return App;
	};

	App.mount = function ( elementOrSelector: string | Element, hydrating: boolean ): InstanceType<typeof App> {
		// Mimic the Vue 3 behavior of appending to the element rather than replacing it
		// Add a div to the element, and pass that to .$mount() so that it gets replaced.
		const wrapperElement = document.createElement( 'div' ),
			parentElement = typeof elementOrSelector === 'string' ?
				document.querySelector( elementOrSelector ) : elementOrSelector;
		if ( !parentElement || !parentElement.appendChild ) {
			throw new Error( 'Cannot find element: ' + elementOrSelector );
		}
		// Remove any existing children from parentElement.
		while ( parentElement.firstChild ) {
			parentElement.removeChild( parentElement.firstChild );
		}
		parentElement.appendChild( wrapperElement );
		const app = new App( finalOptions );
		app.$mount( wrapperElement, hydrating );
		return app;
	};
	return App;
};

export default vue;
