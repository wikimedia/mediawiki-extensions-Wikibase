# -*- encoding : utf-8 -*-
# Wikidata UI tests
#
# Author:: Tobias Gritschacher (tobias.gritschacher@wikimedia.de)
# License:: GNU GPL v2+
#
# Reused and modified from https://github.com/wikimedia/qa-browsertests/blob/master/features/support/env.rb
#
# setup & bootstrapping

# before all
require "mediawiki/selenium"

require "net/http"
require "active_support/all"
require "require_all"

config = YAML.load_file("config/config.yml")
config.each do |k, v|
  eval("#{k} = '#{v}'")
end

require_all "features/support/modules"
require_all "features/support/pages"
require_all "features/support/utils"

def sauce_browser(test_name, language)
  browsers = YAML.load_file("config/browsers.yml")
  if ENV["BROWSER_LABEL"]
    browser_label = browsers[ENV["BROWSER_LABEL"]]
  else
    browser_label = browsers["firefox_linux"]
  end

  caps = Selenium::WebDriver::Remote::Capabilities.send(browser_label["name"])
  caps.platform = browser_label["platform"]
  caps.version = browser_label["version"]
  caps[:name] = "#{test_name} #{ENV["JOB_NAME"]}"

  require "selenium/webdriver/remote/http/persistent" # http_client
  browser = Watir::Browser.new(
      :remote,
      http_client: Selenium::WebDriver::Remote::Http::Persistent.new,
      url: "http://#{ENV["SAUCE_ONDEMAND_USERNAME"]}:#{ENV["SAUCE_ONDEMAND_ACCESS_KEY"]}@ondemand.saucelabs.com:80/wd/hub",
      desired_capabilities: caps)

  browser
end

Before("@repo_login") do
  abort("WB_REPO_USERNAME environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_USERNAME"]
  abort("WB_REPO_PASSWORD environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_PASSWORD"]
end
