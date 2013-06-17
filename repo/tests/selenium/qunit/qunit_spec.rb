# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# running qunit tests automatically

require 'spec_helper'

test_filters = [
  "dataValues",
  "dataTypes",
  "eachchange",
  "jQuery.ui",
  "templates",
  "valueview",
  "time",
  "coordinate"
]

describe "Running repo QUnit tests" do
  before :all do
    # set up
  end
  context "run repo QUnit tests" do
    it "run wikibase tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=wikibase")
        page.wait_for_qunit_tests
        # hack: focus tests are failing in firefox when run by selenium, so we assume these failures are "allowed"
        if page.qunitTestModuleFail1?
          page.qunitTestModuleFail1.should == "wikibase.ui.Toolbar.Label"
          if page.qunitTestModuleFail2?
            page.qunitTestModuleFail2.should == "wikibase.ui.Toolbar.Button"
          end
          page.qunitTestModuleFail3?.should be_false
        else
          page.qunitTestFail?.should be_false
        end
      end
    end

    test_filters.each do |filter|
      it "run tests for filter " + filter do
        on_page(QUnitPage) do |page|
          page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=" + filter)
          page.wait_for_qunit_tests
          page.qunitTestFail?.should be_false
        end
      end
    end
  end
end
