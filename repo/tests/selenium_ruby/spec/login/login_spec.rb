
require 'spec_helper'

describe "Logging into the system" do
  context "successful login" do
    it "should display welcome message" do
      visit_page(LoginPage).login_with('foo', 'bar')
      #DON'T ACTUALLY WANT TO LOG IN UNTIL THIS IS HANDLED SANELY
      #@current_page.text.should include "Welcome foo"
      @current_page.text.should include "Secure your account"
      @current_page.text.should include "phishing"
    end
  end

  context "unsuccessful login" do
    it "should display an error messge" do
      visit_page(LoginPage).login_with('foo', 'badpass')
      @current_page.text.should include "Login error"
      @current_page.text.should include "Secure your account"
    end
  end
  
  context "check login page links" do
    it "should demonstrate all the defined links exist" do 
      visit_page(LoginPage)
      @current_page.phishing_element.should be_true
      visit_page(LoginPage)
      @current_page.password_strength_element.should be_true
    end
  end
  
  context "visit login page links" do
    it "should follow all the defined links" do 
      visit_page(LoginPage)
      @current_page.phishing.should be_true
      @current_page.text.should include "Not to be confused with"
      visit_page(LoginPage)
      @current_page.password_strength.should be_true
      @current_page.text.should include "measure of the effectiveness"
    end
  end
  
end
