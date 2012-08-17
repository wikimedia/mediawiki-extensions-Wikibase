# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# running qunit tests automatically

require 'spec_helper'

describe "Running QUnit tests" do

  context "run the tests" do
    it "should run tests and wait until they are finished" do
      visit_page(QUnitPage) do |page|
        page.wait_for_qunit_tests
        page.qunitTestFail?.should be_false
      end
    end
  end

end
