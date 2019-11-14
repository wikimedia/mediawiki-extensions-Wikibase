import { GetterTree } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import DataValue from '@/datamodel/DataValue';
import Application, { InitializedApplicationState } from '@/store/Application';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import Reference from '@/datamodel/Reference';
import deepEqual from 'deep-equal';

export const getters: GetterTree<Application, Application> = {
	targetValue(
		state: Application,
		getters: {
			[ key: string ]: ( path: MainSnakPath ) => DataValue;
		},
	): DataValue|null {
		if ( state.applicationStatus !== Status.READY ) {
			return null;
		}

		const entityId = ( state as InitializedApplicationState )[ NS_ENTITY ].id;
		const path = {
			entityId,
			propertyId: state.targetProperty,
			index: 0,
		};

		return getters[
			getter( NS_ENTITY, NS_STATEMENTS, mainSnakGetterTypes.dataValue )
		]( path );
	},

	targetLabel( state: Application ): Term {
		if ( state.targetLabel === null ) {
			return {
				language: 'zxx',
				value: state.targetProperty,
			};
		}

		return state.targetLabel;
	},

	targetReferences( state: Application ): Reference[] {
		if ( state.applicationStatus !== Status.READY ) {
			return [];
		}

		const activeState = state as InitializedApplicationState;
		const entityId = activeState[ NS_ENTITY ].id;
		const statements = activeState[ NS_ENTITY ][ NS_STATEMENTS ][ entityId ][ state.targetProperty ][ 0 ];

		return statements.references ? statements.references : [];
	},

	isTargetStatementModified( state: Application ) {
		if ( state.applicationStatus !== Status.READY ) {
			return false;
		}

		const initState = state as InitializedApplicationState;
		const entityId = initState[ NS_ENTITY ].id;
		return !deepEqual(
			state.originalStatement as Statement,
			initState[ NS_ENTITY ][ NS_STATEMENTS ][ entityId ][ state.targetProperty ][ 0 ],
			{ strict: true },
		);
	},
};
