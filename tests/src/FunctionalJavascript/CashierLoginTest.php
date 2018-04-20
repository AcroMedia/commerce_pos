<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS return form.
 *
 * @group commerce_pos
 */
class CashierLoginTest extends JavascriptTestBase {

  use CommercePosCreateStoreTrait;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $cashier;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    $this->cashier = $this->drupalCreateUser($this->getCashierPermissions());
  }

  /**
   * {@inheritdoc}
   */
  protected function getCashierPermissions() {
    return [
      'access commerce pos pages',
    ];
  }

  /**
   * Tests that the basic login functionality works, does not test the quick-select currently.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCashierLogin() {
    $login_url = Url::fromRoute('commerce_pos.login');
    $this->drupalGet($login_url);

    $this->getSession()->getPage()->fillField('name', $this->cashier->getUsername());
    $this->getSession()->getPage()->fillField('pass', $this->cashier->passRaw);

    $this->getSession()->getPage()->findButton('Login')->click();

    $main_url = Url::fromRoute('commerce_pos.main');
    $this->assertEquals($this->getAbsoluteUrl($main_url->toString()),
      $this->getUrl());
  }

  /**
   * Tests that an bad login keeps the user on the right page.
   *
   * Tried to also test for error messaging but testbot seems to have problems with that.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  public function testCashierLoginFailed() {
    $login_url = Url::fromRoute('commerce_pos.login');

    $this->drupalGet($login_url);

    $this->getSession()->getPage()->fillField('name', 'wronguser');
    $this->getSession()->getPage()->fillField('pass', 'wrongpass');
    $this->getSession()->getPage()->findButton('Login')->click();

    $this->assertEquals($this->getAbsoluteUrl($login_url->toString()), $this->getUrl());
  }

}
