import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import {
	STATEMENTS_CONTAINS_ENTITY,
	STATEMENTS_IS_AMBIGUOUS,
	STATEMENTS_PROPERTY_EXISTS,
} from '@/store/entity/statements/getterTypes';
import StatementsState from '@/store/entity/statements/StatementsState';
import StatementMap from '@/datamodel/StatementMap';

export const statementGetters: GetterTree<StatementsState, Application> = {
	[ STATEMENTS_CONTAINS_ENTITY ]: ( state: StatementsState ) => ( entityId: string ): boolean => {
		return state[ entityId ] !== undefined;
	},

	[ STATEMENTS_PROPERTY_EXISTS ]: ( state: StatementsState ) => ( entityId: string, propertyId: string ): boolean => {
		return ( state[ entityId ] as StatementMap )[ propertyId ] !== undefined;
	},

	[ STATEMENTS_IS_AMBIGUOUS ]: ( state: StatementsState ) => ( entityId: string, propertyId: string ): boolean => {
		return ( state[ entityId ] as StatementMap )[ propertyId ] !== undefined
			&& ( state[ entityId ] as StatementMap )[ propertyId ].length > 1;
	},
};
