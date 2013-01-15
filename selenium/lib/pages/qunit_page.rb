# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# page object for qunit tests

class QUnitPage
  include PageObject

  h1(:mwFirstHeading, :id => "firstHeading")
  paragraph(:qunitTestResult, :id => "qunit-testresult")
  ordered_list(:qunitTestList, :id => "qunit-tests")
  list_item(:qunitTestFail, :class => "fail")
  span(:qunitTestModuleFail1, :xpath => "//li[@class='fail'][1]/strong/span")
  span(:qunitTestModuleFail2, :xpath => "//li[@class='fail'][2]/strong/span")
  span(:qunitTestModuleFail3, :xpath => "//li[@class='fail'][3]/strong/span")
  list_item(:qunitTestRunning, :class => "running")

  def wait_for_qunit_tests
    wait_until do
      qunitTestResult? && qunitTestList? && (qunitTestRunning? == false)
    end
  end

  def call_qunit(url)
    navigate_to url
  end
end
