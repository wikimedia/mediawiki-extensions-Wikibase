# dist/ size over time

This tracks the size of the `dist/` directory (the resources shipped to clients) of a Wikibase component over time.
The `analyze` script goes through the Git history (via the github API) and collects data for each relevant commit,
and then writes that data file, plus a website which shows it, to a specified output directory.

## Usage

For Wikidata Bridge and Tainted References, scripts are already set up in `package.json`.
Run either of the following commands:

    npm run doc:data-bridge-dist-size
    npm run doc:tainted-ref-dist-size

And then open `docs/data-bridge-dist-size` or `docs/tainted-ref-dist-size`, respectively, in your browser.
(Note that they must be served from an HTTP server; the `file://` protocol will not work.)

You can also run the `analyze` script directly.
It expects the output directory, github repository owner and repository, and then any number of files to analyze as command line arguments:

    node build/dist-size/analyze OUTPUT GITHUB_REPO_OWNER GITHUB_REPO FILES...

In order to communicate with the github API, a token (which is user-specific and can not be checked-in with the code) is required.
Please create one for that purpose on the [respective github setting page](https://github.com/settings/tokens), granting at least the permission `repo:status`,
and assign it to the environment variable `COMPOSER_GITHUB_OAUTHTOKEN`.
In CI the jenkins administrator did that for us.

Note that the output directory must not yet exist; `rm -rf` it before running the script if necessary.
(Node only natively support recursive directory removal since v12, and it’s still experimental,
so for now it doesn’t seem worth the effort to add this feature to the script itself.)
