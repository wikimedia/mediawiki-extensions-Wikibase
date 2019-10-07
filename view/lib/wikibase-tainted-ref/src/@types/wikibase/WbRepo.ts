/** basic client-side configuration for a Wikibase repository */
interface WbRepo {
	/** base URL of the repository */
	url: string;
	/** location of index.php and api.php relative to url */
	scriptPath: string;
	/** location of articles (replace $1 with title) relative to url */
	articlePath: string;
}

export default WbRepo;
