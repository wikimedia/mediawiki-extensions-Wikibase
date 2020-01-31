import { rootModule } from '@/store';
import { RootActions } from '@/store/actions';
import Application from '@/store/Application';
import { BaseState } from '@/store/state';
import { Store } from 'vuex';
import {
	Actions,
	createStore as smartCreateStore,
} from 'vuex-smart-module';

afterEach( () => {
	rootModule.options.state = BaseState;
	rootModule.options.actions = RootActions;
} );

export function createTestStore( { state, actions }: {
	state?: Partial<Application>;
	actions?: Partial<RootActions>;
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
	return smartCreateStore( rootModule );
}
