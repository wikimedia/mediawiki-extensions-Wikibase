# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# running qunit tests automatically

require 'spec_helper'

describe "Running repo QUnit tests" do
  before :all do
    # set up
  end
  context "run repo QUnit tests" do
    it "run wikibase tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=wikibase")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
    it "run dataValues tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=dataValues")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
    it "run dataTypes tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=dataTypes")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
    it "run jQuery.ui tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=jQuery.ui")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
    it "run template engine tests" do
      on_page(QUnitPage) do |page|
        page.call_qunit(WIKI_REPO_URL + "Special:JavaScriptTest/qunit?filter=templates")
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
  end
end
