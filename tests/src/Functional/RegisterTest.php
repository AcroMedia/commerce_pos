<?php

namespace Drupal\Tests\commerce_pos\Functional;

use Drupal\commerce_pos\Entity\Register;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;

/**
 * Ensure the Register Entity is works correctly.
 *
 * @group commerce.
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
      'id' => $title,
      'label' => $title,
    ]);
    $register_exists = (bool) Register::load($register->id());
    $this->assertNotEmpty($register_exists, 'The Register has been created in the database');

    // Create Register through the form.
    $edit = [
      'id' => 'foo',
      'label' => 'Label of foo',
    ];
    $this->drupalPostForm("admin/commerce/config/pos/register/add", $edit, 'Save');
    $register_exists = (bool) Register::load($edit['id']);
    $this->assertNotEmpty($register_exists, 'The Register has been created in the database');
  }

  /**
   * Tests for update through the form.
   */
  public function testUpdateRegister() {
    // Create a new Register.
    $register_new = $this->createEntity('commerce_pos_register', [
      'id' => 'foo',
      'label' => 'Label of foo',
    ]);

    $this->drupalGet('admin/commerce/config/pos/register/foo/edit');
    // Only label is updating.
    $edit = [
      'label' => $this->randomMachineName(8),
    ];
    $this->submitForm($edit, 'Save');
    $register_updated = Register::load($register_new->id());
    $this->assertEquals($edit['label'], $register_updated->label(), 'The label of the Register has been updated.');
  }

  /**
   * Tests for delete through the form.
   */
  public function testDeleteRegister() {
    // Create a new register.
    $register = $this->createEntity('commerce_pos_register', [
      'id' => 'foo',
      'label' => 'Label of foo',
    ]);

    $this->drupalGet('admin/commerce/config/pos/register/foo/delete');
    $this->assertSession()->pageTextContains(t('Are you sure you want to delete @type?', ['@type' => $register->label()]));
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->submitForm([], 'Delete');
    $register_exists = (bool) Register::load($register->id());
    $this->assertEmpty($register_exists, 'The new register has been deleted from the database.');
  }

}
