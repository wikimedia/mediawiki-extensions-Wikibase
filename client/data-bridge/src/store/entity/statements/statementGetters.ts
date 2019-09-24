import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_MAP,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import StatementsState from '@/store/entity/statements/StatementsState';
import StatementMap from '@/datamodel/StatementMap';
import EntityId from '@/datamodel/EntityId';

export const statementGetters: GetterTree<StatementsState, Application> = {
	[ STATEMENTS_CONTAINS_ENTITY ]: ( state: StatementsState ) => ( entityId: EntityId ): boolean => {
		return state[ entityId ] !== undefined;
	},

	[ STATEMENTS_PROPERTY_EXISTS ]: ( state: StatementsState ) => (
		entityId: EntityId,
		propertyId: EntityId,
	): boolean => {
		return ( state[ entityId ] as StatementMap )[ propertyId ] !== undefined;
	},

	[ STATEMENTS_IS_AMBIGUOUS ]: ( state: StatementsState ) => (
		entityId: EntityId,
		propertyId: EntityId,
	): boolean => {
		return ( state[ entityId ] as StatementMap )[ propertyId ] !== undefined
			&& ( state[ entityId ] as StatementMap )[ propertyId ].length > 1;
	},

	[ STATEMENTS_MAP ]: ( state: StatementsState ) => ( entityId: EntityId ): StatementMap => {
		return state[ entityId ] as StatementMap;
	},
};
