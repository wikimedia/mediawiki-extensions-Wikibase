import { DataValue } from '@wmde/wikibase-datamodel-types';
import Term from '@/datamodel/Term';
import ApplicationError from '@/definitions/ApplicationError';
import { ValidApplicationStatus } from '@/definitions/ApplicationStatus';
import EditDecision from '@/definitions/EditDecision';
import Application, { BridgeConfig } from '@/store/Application';

export class BaseState implements Application {
	public applicationErrors: ApplicationError[] = [];
	public applicationStatus: ValidApplicationStatus = ValidApplicationStatus.INITIALIZING;
	public editDecision: EditDecision|null = null;
	public targetValue: DataValue|null = null;
	public renderedTargetReferences: readonly string[] = [];
	public editFlow = '';
	public entityTitle = '';
	public originalHref = '';
	public pageTitle = '';
	public targetLabel: Term|null = null;
	public targetProperty = '';
	public pageUrl = '';
	public showWarningAnonymousEdit = false;
	public assertUserWhenSaving = true;
	public config: BridgeConfig = {
		usePublish: null,
		issueReportingLink: null,
		stringMaxLength: null,
		dataRightsText: null,
		dataRightsUrl: null,
		termsOfUseUrl: null,
	};
}
