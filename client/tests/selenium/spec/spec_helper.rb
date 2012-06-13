$LOAD_PATH.unshift(File.dirname(__FILE__))
$LOAD_PATH.unshift(File.join(File.dirname(__FILE__), ',,', 'lib'))

require 'rspec'
require 'rspec/expectations'
require 'yaml'
require 'watir-webdriver'
require 'page-object'
require 'page-object/page_factory'
require 'require_all'

require_all 'lib/pages'

# TODO: must this really be global?
$target_browser = "firefox" # "chrome" "ie" "opera" "safari" "firefox"

RSpec.configure do |config|
  config.include PageObject::PageFactory
  config.before(:all) do
    case $target_browser
    when "firefox"
      @browser = Watir::Browser.new :firefox
    when "chrome"
      @browser = Watir::Browser.new :chrome
    when "ie"
      @browser = Watir::Browser.new :ie
    when "opera"
      @browser = Watir::Browser.new :opera
    when "safari"
      @browser = Watir::Browser.new :safari
    else
      @browser = Watir::Browser.new :firefox
    end
  end

  config.after(:all) do
    @browser.close
  end

end

