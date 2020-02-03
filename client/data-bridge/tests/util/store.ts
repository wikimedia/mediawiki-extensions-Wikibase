import { rootModule } from '@/store';
import { RootActions } from '@/store/actions';
import { RootGetters } from '@/store/getters';
import Application from '@/store/Application';
import { BaseState } from '@/store/state';
import { Store } from 'vuex';
import {
	Actions,
	Getters,
	createStore as smartCreateStore,
} from 'vuex-smart-module';

afterEach( () => {
	rootModule.options.state = BaseState;
	rootModule.options.actions = RootActions;
	rootModule.options.getters = RootGetters;
} );

export function createTestStore( { state, actions, getters }: {
	state?: Partial<Application>;
	actions?: Partial<RootActions>;
	getters?: Partial<RootGetters>;
} = {} ): Store<any> {
	if ( state !== undefined ) {
		rootModule.options.state = class implements Partial<Application> {
			public constructor() {
				Object.assign( this, state );
			}
		} as new() => Application;
	}
	if ( actions !== undefined ) {
		rootModule.options.actions = class extends Actions {} as new() => RootActions;
		Object.assign( rootModule.options.actions.prototype, actions );
	}
	if ( getters !== undefined ) {
		rootModule.options.getters = class extends Getters {} as new() => RootGetters;
		Object.assign( rootModule.options.getters.prototype, getters );
	}
	return smartCreateStore( rootModule );
}
