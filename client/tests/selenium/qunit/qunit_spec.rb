# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# running qunit tests automatically

require 'spec_helper'

describe "Running client QUnit tests" do
  before :all do
    # set up
  end
  context "run client QUnit tests" do
    it "run wikibase client tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_CLIENT_URL + "Special:JavaScriptTest/qunit?filter=wikibase.client")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
  end
end
