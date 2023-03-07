import { rootModule } from '@/store';
import { RootActions } from '@/store/actions';
import { EntityState } from '@/store/entity/EntityState';
import { RootGetters } from '@/store/getters';
import Application from '@/store/Application';
import { NS_ENTITY } from '@/store/namespaces';
import { BaseState } from '@/store/BaseState';
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
	rootModule.options.modules![ NS_ENTITY ].options.state = EntityState;
} );

type Mutable<T> = {
	-readonly [P in keyof T]: T[P];
};

export type MutableStore<T> = Mutable<Store<T>>;

export function createTestStore( { state, actions, getters, entityState }: {
	state?: Partial<Application>;
	actions?: Partial<RootActions>;
	getters?: Partial<RootGetters>;
	entityState?: Partial<EntityState>;
} = {} ): Store<any> {
	if ( state !== undefined ) {
		rootModule.options.state = class extends BaseState {
			public constructor() {
				super();
				Object.assign( this, state );
			}
		};
	}
	if ( actions !== undefined ) {
		rootModule.options.actions = class extends Actions {} as new() => RootActions;
		Object.assign( rootModule.options.actions.prototype, actions );
	}
	if ( getters !== undefined ) {
		rootModule.options.getters = class extends Getters {} as new() => RootGetters;
		Object.defineProperties( rootModule.options.getters.prototype,
			Object.getOwnPropertyDescriptors( getters ) );
	}
	if ( entityState !== undefined ) {
		rootModule.options.modules![ NS_ENTITY ].options.state = class extends EntityState {
			public constructor() {
				super();
				Object.assign( this, entityState );
			}
		};
	}
	return smartCreateStore( rootModule );
}
