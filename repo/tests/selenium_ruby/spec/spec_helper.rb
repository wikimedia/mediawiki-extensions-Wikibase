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



RSpec.configure do |config|
  config.include PageObject::PageFactory
  
  config.before(:all) do 
    @browser = Watir::Browser.new :firefox
  end

  config.after(:all) do
    @browser.close
  end
  
end

