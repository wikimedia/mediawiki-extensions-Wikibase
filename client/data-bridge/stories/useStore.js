import { app } from '@storybook/vue3';
import { rootModule } from '@/store';
import { createStore as smartCreateStore } from 'vuex-smart-module';

export default function useStore( state ) {
	return () => {
		rootModule.options.state = class {
			constructor() {
				Object.assign( this, state );
			}
		};
		const store = smartCreateStore( rootModule );
		app.use( store );
		return {
			store,
			template: '<story/>',
		};
	};
}
