<?php

namespace Drupal\Tests\commerce_pos\Functional;

use Drupal\commerce_pos\Entity\Register;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Ensure the Register Entity is works correctly.
 *
 * @group commerce_pos
 */
class RegisterTest extends CommerceBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'system',
    'block',
    'field',
    'commerce',
    'commerce_price',
    'commerce_store',
    'commerce_pos',
  ];

  /**
   * {@inheritdoc}
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser($this->getAdministratorPermissions());
    $this->drupalLogin($this->adminUser);

  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return [
      'view the administration theme',
      'access administration pages',
      'access commerce administration pages',
      'administer commerce_currency',
      'administer commerce_store',
      'administer commerce_store_type',
      'access commerce pos administration pages',
    ];
  }

  /**
   * Tests for creating register programatically and through the form.
   */
  public function testCreateRegister() {
    $title = strtolower($this->randomMachineName(8));

    // Create Register programmaticaly.
    $register = $this->createEntity('commerce_pos_register', [
      'name' => $title,
      'cash' => 100,
    ]);
    $register_exists = (bool) Register::load($register->id());
    $this->assertNotEmpty($register_exists, 'The Register has been created in the database');

    // Create Register through the form.
    $edit = [
      'name[0][value]' => 'foo',
      'cash[0][number]' => 100,
    ];
    $this->drupalPostForm("admin/commerce/config/pos/register/add", $edit, 'Save');
    $register_exists = (bool) Register::load(1);
    $this->assertNotEmpty($register_exists, 'The Register has been created in the database');
  }

  /**
   * Tests for update through the form.
   */
  public function testUpdateRegister() {
    // Create a new Register.
    $register_new = $this->createEntity('commerce_pos_register', [
      'name' => 'foo',
      'cash' => 100,
    ]);

    $this->drupalGet('admin/commerce/config/pos/register/' . $register_new->id() . '/edit');
    // Only name is updated.
    $edit = [
      'name[0][value]' => $this->randomMachineName(8),
      'cash[0][number]' => 100,
    ];
    $this->submitForm($edit, 'Save');
    \Drupal::entityTypeManager()->getStorage('commerce_pos_register')->resetCache(array($register_new->id()));
    $register_updated = Register::load($register_new->id());
    $this->assertEquals($edit['name[0][value]'], $register_updated->getName(), 'The name of the Register has been updated.');
  }

  /**
   * Tests for delete through the form.
   */
  public function testDeleteRegister() {
    // Create a new register.
    $register = $this->createEntity('commerce_pos_register', [
      'name' => 'foo',
      'cash' => 100,
    ]);

    $this->drupalGet('admin/commerce/config/pos/register/' . $register->id() . '/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete the register @name?', ['@name' => $register->getName()]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');
    \Drupal::entityTypeManager()->getStorage('commerce_pos_register')->resetCache(array($register->id()));
    $register_exists = (bool) Register::load($register->id());
    $this->assertFalse($register_exists, 'The new register has been deleted from the database.');
  }

}
