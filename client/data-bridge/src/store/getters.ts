import { GetterTree } from 'vuex';
import Status from '@/definitions/ApplicationStatus';
import DataValue from '@/datamodel/DataValue';
import Application, {
	InitializedApplicationState,
} from '@/store/Application';
import ApplicationStatus from '@/definitions/ApplicationStatus';
import {
	NS_ENTITY,
	NS_STATEMENTS,
} from '@/store/namespaces';
import {
	ENTITY_ID,
} from '@/store/entity/getterTypes';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import { getter } from '@wmde/vuex-helpers/dist/namespacedStoreMethods';
import Term from '@/datamodel/Term';
import Statement from '@/datamodel/Statement';
import deepEqual from 'deep-equal';

export const getters: GetterTree<Application, Application> = {
	editFlow( state: Application ): string {
		return state.editFlow;
	},

	targetProperty( state: Application ): string {
		return state.targetProperty;
	},

	applicationStatus( state: Application ): ApplicationStatus {
		return state.applicationStatus;
	},

	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	targetValue( state: Application, getters: { [ key: string ]: any } ): DataValue|null {
		if ( state.applicationStatus !== Status.READY ) {
			return null;
		}

		const entityId = getters[ getter( NS_ENTITY, ENTITY_ID ) ];
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

	stringMaxLength( state: Application ): number|null {
		if ( state.wikibaseRepoConfiguration === null ) {
			return null;
		}

		return state.wikibaseRepoConfiguration.dataTypeLimits.string.maxLength;
	},

	isTargetStatementModified( state: Application, getters: { [ key: string ]: string } ): boolean {
		if ( state.applicationStatus !== Status.READY ) {
			return false;
		}

		const initState = state as InitializedApplicationState;
		const entityId = getters[ getter( NS_ENTITY, ENTITY_ID ) ];
		return !deepEqual(
			state.originalStatement as Statement,
			initState[ NS_ENTITY ][ NS_STATEMENTS ][ entityId ][ state.targetProperty ][ 0 ],
			{ strict: true },
		);
	},
};
