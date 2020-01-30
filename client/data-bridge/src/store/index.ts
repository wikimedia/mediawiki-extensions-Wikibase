import Vue from 'vue';
import Vuex, { Store } from 'vuex';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import Application from '@/store/Application';
import { RootActions } from '@/store/actions';
import { RootGetters } from '@/store/getters';
import { RootMutations } from '@/store/mutations';
import { createStore as smartCreateStore, Module } from 'vuex-smart-module';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import EditDecision from '@/definitions/EditDecision';
import ApplicationError from '@/definitions/ApplicationError';
import { WikibaseRepoConfiguration } from '@/definitions/data-access/WikibaseRepoConfigRepository';
import ServiceContainer from '@/services/ServiceContainer';
import { NS_ENTITY, NS_STATEMENTS } from '@/store/namespaces';
import { entityModule } from './entity';
import { statementModule } from '@/store/statements';

Vue.use( Vuex );

class BaseState implements Application {
	public applicationErrors: ApplicationError[] = [];
	public applicationStatus: ValidApplicationStatus = ValidApplicationStatus.INITIALIZING;
	public editDecision: EditDecision|null = null;
	public editFlow = '';
	public entityTitle = '';
	public originalHref = '';
	public originalStatement: Statement|null = null;
	public pageTitle = '';
	public targetLabel: Term|null = null;
	public targetProperty = '';
	public wikibaseRepoConfiguration: WikibaseRepoConfiguration|null = null;
}

export const rootModule = new Module( {
	state: BaseState,
	getters: RootGetters,
	mutations: RootMutations,
	actions: RootActions,
	modules: {
		[ NS_ENTITY ]: entityModule,
		[ NS_STATEMENTS ]: statementModule,
	},
} );

export function createStore( services: ServiceContainer ): Store<Application> {

	const store = smartCreateStore( rootModule, {
		strict: process.env.NODE_ENV !== 'production',
	} );

	store.$services = services;
	return store;
}
