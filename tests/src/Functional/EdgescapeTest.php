<?php

namespace Drupal\Tests\akamai\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\akamai\Helper\Edgescape;
use Drupal\block_content\Entity\BlockContent;
use Drupal\block_content\Entity\BlockContentType;
use Drupal\Core\Url;

/**
 * Tests Edgescape Token via X-Akamai-Edgescape Header.
 *
 * @group Akamai
 */
class EdgescapeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['akamai', 'token', 'block', 'block_content'];

  /**
   * Test the header value.
   */
  public function testTokenOutputFromHeader() {
    // Create user and login.
    $user = $this->drupalCreateUser([
      'administer blocks',
      'administer akamai',
    ]);
    $this->drupalLogin($user);

    // Enable Edgescape support.
    $akamai_config_path = Url::fromRoute('akamai.settings')->getInternalPath();
    $edit[Edgescape::EDGESCAPE_SUPPORT] = TRUE;
    $this->drupalGet($akamai_config_path);
    $this->submitForm($edit, t('Save configuration'));

    // Create block, block content, and place.
    $label = 'tokenblock';
    $bundle = BlockContentType::create([
      'id' => $label,
      'label' => $label,
      'revision' => FALSE,
    ]);
    $bundle->save();
    $block_content = BlockContent::create([
      'type' => $label,
      'label' => '',
      'info' => 'Test token title block',
      'body[value]' => 'This is the test token title block.',
    ]);
    $block_content->save();
    $block = $this->drupalPlaceBlock('block_content:' . $block_content->uuid(), [
      'label' => 'Country Code: [akamai:edgescape:country_code]',
    ]);

    // Open akamai config page and verify block output,
    // using this page is helpful to verify Edgescape is enabled.
    $header_value = 'country_code=RAX,continent=NA';
    $this->drupalGet($akamai_config_path, [], [
      Edgescape::EDGESCAPE_HEADER => $header_value,
    ]);
    $this->assertRaw('Country Code: RAX');
  }

}
