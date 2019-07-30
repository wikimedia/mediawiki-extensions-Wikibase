import AppInformation from '@/definitions/AppInformation';

export default interface ApplicationInformationRepository {
	getInformation(): Promise<AppInformation>;
}
