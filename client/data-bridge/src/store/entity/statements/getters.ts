import { GetterTree } from 'vuex';
import Application from '@/store/Application';
import StatementsState from '@/store/entity/statements/StatementsState';
import { statementGetters } from '@/store/entity/statements/statementGetters';
import { mainSnakGetterTypes } from '@/store/entity/statements/mainSnakGetterTypes';
import resolveMainSnak from '@/store/entity/statements/resolveMainSnak';
import buildMainSnakGetters from '@/store/entity/statements/snaks/getters';
import MainSnakPath from '@/store/entity/statements/MainSnakPath';

const mainSnakGetters = buildMainSnakGetters<MainSnakPath>(
	mainSnakGetterTypes, resolveMainSnak,
);

export const getters: GetterTree<StatementsState, Application> = {
	...statementGetters,
	...mainSnakGetters,
};
