import { DataValue } from '@wmde/wikibase-datamodel-types';
import {
	NS_ENTITY, NS_STATEMENTS,
} from './namespaces';
import { EntityState } from '@/store/entity/EntityState';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import Term from '@/datamodel/Term';
import ApplicationError from '@/definitions/ApplicationError';
import EditDecision from '@/definitions/EditDecision';
import { StatementState } from '@/store/statements/StatementState';

export interface BridgeConfig {
	usePublish: boolean | null;
	issueReportingLink: string | null;
	stringMaxLength: number | null;
	dataRightsText: string | null;
	dataRightsUrl: string | null;
	termsOfUseUrl: string | null;
}

interface Application {
	applicationErrors: ApplicationError[];
	applicationStatus: ValidApplicationStatus;
	editDecision: EditDecision|null;
	targetValue: DataValue|null;
	renderedTargetReferences: readonly string[];
	editFlow: string;
	entityTitle: string;
	originalHref: string;
	pageTitle: string;
	targetLabel: Term|null;
	targetProperty: string;
	pageUrl: string;
	showWarningAnonymousEdit: boolean;
	assertUserWhenSaving: boolean;
	config: BridgeConfig;
}

export default Application;

export interface InitializedApplicationState extends Application {
	[ NS_ENTITY ]: EntityState;
	[ NS_STATEMENTS ]: StatementState;
}

export interface SavingState extends InitializedApplicationState {
	editDecision: EditDecision;
	targetValue: DataValue;
	applicationStatus: ValidApplicationStatus.SAVING;
}
