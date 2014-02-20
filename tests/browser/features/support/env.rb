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
require "mediawiki_selenium"

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

def local_browser(language)
  if ENV["BROWSER"]
    browser_name = ENV["BROWSER"].to_sym
  else
    browser_name = :firefox
  end

  client = Selenium::WebDriver::Remote::Http::Default.new
  profile = Selenium::WebDriver::Firefox::Profile.new

	if ENV["BROWSER_TIMEOUT"] && browser_name == :firefox
	  timeout = ENV["BROWSER_TIMEOUT"].to_i
	  client.timeout = timeout
    profile["dom.max_script_run_time"] = timeout
	end

  if language == "default"
    browser = Watir::Browser.new browser_name, :http_client => client, :profile => profile
  else
    if browser_name == :firefox
      profile["intl.accept_languages"] = language
      browser = Watir::Browser.new browser_name, :profile => profile, :http_client => client
    elsif browser_name == :chrome
      profile = Selenium::WebDriver::Chrome::Profile.new
      profile["intl.accept_languages"] = language
      browser = Watir::Browser.new browser_name, :profile => profile, :http_client => client
    elsif browser_name == :phantomjs
      capabilities = Selenium::WebDriver::Remote::Capabilities.phantomjs
      capabilities["phantomjs.page.customHeaders.Accept-Language"] = language
      browser = Watir::Browser.new browser_name, desired_capabilities: capabilities, :http_client => client
    else
      raise "Changing default language is currently supported only for Chrome, Firefox and PhantomJS!"
    end
  end

  browser.window.resize_to 1280, 1024
  browser
end

Before("@repo_login") do
  abort("WB_REPO_USERNAME environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_USERNAME"]
  abort("WB_REPO_PASSWORD environment variable is not defined! Please export a value for that variable before proceeding.") unless ENV["WB_REPO_PASSWORD"]
end
