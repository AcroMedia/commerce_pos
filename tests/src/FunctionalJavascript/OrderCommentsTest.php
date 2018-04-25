<?php

namespace Drupal\Tests\commerce_pos\FunctionalJavascript;

use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Html;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\Tests\commerce_pos\Functional\CommercePosCreateStoreTrait;

/**
 * Tests the Commerce POS form.
 *
 * @group commerce_pos
 */
class OrderCommentsTest extends JavascriptTestBase {

  use CommercePosCreateStoreTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'search_api_db',
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpStore();

    // @todo work out the expected permissions to view products etc...
    $this->drupalLogin($this->rootUser);
  }

  /**
   * Tests comments on POS orders.
   */
  public function testCommercePosFormOrderComments() {
    $web_assert = $this->assertSession();
    $this->drupalGet('admin/commerce/pos/main');

    $this->getSession()->getPage()->fillField('register', '1');
    $this->getSession()->getPage()->fillField('float[number]', '10.00');
    $this->getSession()->getPage()->findButton('Open Register')->click();

    // Now we should be able to select order items.
    $autocomplete_field = $this->getSession()->getPage()->findField('order_items[target_id][product_selector]');
    $autocomplete_field->setValue('Jumper X');
    $this->getSession()->getDriver()->keyDown($autocomplete_field->getXpath(), 'L');
    $web_assert->waitOnAutocomplete();
    $results = $this->getSession()->getPage()->findAll('css', '.ui-autocomplete li');
    $this->assertCount(1, $results);
    // Click on of the auto-complete.
    $results[0]->click();
    $web_assert->assertWaitOnAjaxRequest();

    // Add a comment.
    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', 'Test comment');
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $results = $this->getSession()->getPage()->findAll('css', '.view-commerce-activity table tr');
    $this->assertCount(2, $results);
    $this->assertContains('Test comment', $results[1]->getText());

    // Add another comment with XSS.
    $this->getSession()->getPage()->findButton('Back To Order')->click();
    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', "<script>alert('here');</script>");
    $this->getSession()->getPage()->findButton('Payments and Completion')->click();
    $results = $this->getSession()->getPage()->findAll('css', '.view-commerce-activity table tr');
    $this->assertCount(3, $results);
    $web_assert->pageTextContains("<script>alert('here');</script>");
    $web_assert->pageTextContains('Test comment');

    $this->getSession()->getPage()->fillField('order_comments[add_order_comment][order_comment_text]', "Test parked");
    $this->getSession()->getPage()->findButton('Park Order')->click();

    // Ensure the comment has been logged and saved.
    $logStorage = $this->container->get('entity_type.manager')->getStorage('commerce_log');
    $order = Order::load(1);
    $logs = $logStorage->loadMultipleByEntity($order);
    $this->assertEquals(3, count($logs));
    $logViewBuilder = $this->container->get('entity_type.manager')->getViewBuilder('commerce_log');
    $build = $logViewBuilder->view($logs[1]);
    $this->assertContains('Test comment', (string) $this->container->get('renderer')->renderPlain($build));
    $build = $logViewBuilder->view($logs[2]);
    // The script tag should be escaped.
    $this->assertContains(Html::escape("<script>alert('here');</script>"), (string) $this->container->get('renderer')->renderPlain($build));
    $build = $logViewBuilder->view($logs[3]);
    $this->assertContains('Test parked', (string) $this->container->get('renderer')->renderPlain($build));

    // View the order via the regular interface, the comment should also be here.
    $this->drupalGet($order->toUrl());
    $web_assert->pageTextContains('Test comment');
    $web_assert->responseNotContains("<script>alert('here');</script>");
    $web_assert->pageTextContains("<script>alert('here');</script>");
    $web_assert->pageTextContains('Test parked');
  }

  /**
   * Waits for jQuery to become active and animations to complete.
   */
  protected function waitForAjaxToFinish() {
    $condition = "(0 === jQuery.active && 0 === jQuery(':animated').length)";
    $this->assertJsCondition($condition, 10000);
  }

}
