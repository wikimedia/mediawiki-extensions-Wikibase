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

def sauce_browser(test_name, language)
  browsers = YAML.load_file("config/browsers.yml")
  if ENV["BROWSER_LABEL"]
    browser_label = browsers[ENV["BROWSER_LABEL"]]
  else
    browser_label = browsers["firefox_linux"]
  end

  require "selenium/webdriver/remote/http/persistent" # http_client
  client = Selenium::WebDriver::Remote::Http::Persistent.new

  if ENV["BROWSER_TIMEOUT"] && browser_label['name'] == 'firefox'
    timeout = ENV["BROWSER_TIMEOUT"].to_i
    client.timeout = timeout
  end

  if browser_label['name'] == 'firefox'
    profile = Selenium::WebDriver::Firefox::Profile.new
    if timeout
      profile["dom.max_script_run_time"] = timeout
      profile["dom.max_chrome_script_run_time"] = timeout
    end
    profile['intl.accept_languages'] = language
    caps = Selenium::WebDriver::Remote::Capabilities.firefox(:firefox_profile => profile)
  elsif browser_label['name'] == 'chrome'
    profile = Selenium::WebDriver::Chrome::Profile.new
    profile['intl.accept_languages'] = language
    caps = Selenium::WebDriver::Remote::Capabilities.chrome('chrome.profile' => profile.as_json['zip'])
  else
    caps = Selenium::WebDriver::Remote::Capabilities.send(browser_label['name'])
  end

  caps.platform = browser_label["platform"]
  caps.version = browser_label["version"]
  caps[:name] = "#{test_name} #{ENV["JOB_NAME"]}"

  browser = Watir::Browser.new(
      :remote,
      http_client: client,
      url: "http://#{ENV["SAUCE_ONDEMAND_USERNAME"]}:#{ENV["SAUCE_ONDEMAND_ACCESS_KEY"]}@ondemand.saucelabs.com:80/wd/hub",
      desired_capabilities: caps)

  browser
end

def local_browser(language)
  if ENV["BROWSER_LABEL"]
    browser_label = ENV["BROWSER_LABEL"].to_sym
  else
    browser_label = :firefox
  end

  client = Selenium::WebDriver::Remote::Http::Default.new
  profile = Selenium::WebDriver::Firefox::Profile.new

	if ENV["BROWSER_TIMEOUT"] && browser_label == :firefox
	  timeout = ENV["BROWSER_TIMEOUT"].to_i
	  client.timeout = timeout
    profile["dom.max_script_run_time"] = timeout
    profile["dom.max_chrome_script_run_time"] = timeout
	end

  if language == "default"
    browser = Watir::Browser.new browser_label, :http_client => client, :profile => profile
  else
    if browser_label == :firefox
      profile["intl.accept_languages"] = language
      browser = Watir::Browser.new browser_label, :profile => profile, :http_client => client
    elsif browser_label == :chrome
      profile = Selenium::WebDriver::Chrome::Profile.new
      profile["intl.accept_languages"] = language
      browser = Watir::Browser.new browser_label, :profile => profile, :http_client => client
    elsif browser_label == :phantomjs
      capabilities = Selenium::WebDriver::Remote::Capabilities.phantomjs
      capabilities["phantomjs.page.customHeaders.Accept-Language"] = language
      browser = Watir::Browser.new browser_label, desired_capabilities: capabilities, :http_client => client
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
