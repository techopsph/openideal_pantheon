<?php

namespace Drupal\rrssb\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * RRSSB settings form.
 *
 * @package Drupal\rrssb\Form
 */
class RRSSBSettingsForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $all_buttons = rrssb_button_config();
    $form = parent::form($form, $form_state);
    $config = $this->entity;
    $chosen = $config->get('chosen');

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#default_value' => $config->label(),
      '#required' => TRUE,
      '#description' => $this->t('Administrative label for this button set.'),
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#default_value' => $config->id(),
      '#disabled' => !$config->isNew(),
      '#required' => TRUE,
    ];

    $form['follow'] = [
      '#type' => 'select',
      '#title' => $this->t('Select type of buttons'),
      '#options' => [
        0 => $this->t('Share'),
        1 => $this->t('Follow'),
      ],
      '#default_value' => $config->get('follow'),
      '#description' => $this->t('"Share" buttons invite the visitor to share the page from your site onto their page/channel/profile.  "Follow" buttons direct the visitor to your page/channel/profile.'),
    ];

    // Create the config for the table of buttons.
    $form['chosen'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Button'),
        $this->t('Enabled'),
        $this->t('Username'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No buttons found'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'item-row-weight',
        ],
      ],
    ];

    foreach ($all_buttons as $name => $button) {
      $form['chosen'][$name]['#attributes']['class'][] = 'draggable';

      // Determine if this button requires a particular value of follow
      // to be valid. This is the case if one or other of the URL as not
      // present.
      // Both URLs absent makes no sense and would be a bug.
      unset($require_follow);
      if (!isset($button['follow_url'])) {
        $require_follow = 0;
      }
      elseif (!isset($button['share_url'])) {
        $require_follow = 1;
      }
      $form['chosen'][$name]['label'] = [
        '#type' => 'item',
        '#markup' => $name,
      ];
      $form['chosen'][$name]['enabled'] = [
        '#type' => 'checkbox',
        '#default_value' => isset($chosen[$name]['enabled']) ? $chosen[$name]['enabled'] : FALSE,
      ];
      if (isset($require_follow)) {
        // Hide entries where there is no corresponding URL.
        $form['chosen'][$name]['enabled']['#states'] = [
          'visible' => [":input[name='follow']" => ['value' => $require_follow]],
        ];
      }
      if (isset($button['follow_url']) && strpos($button['follow_url'], '[rrssb:username]') !== FALSE) {
        $form['chosen'][$name]['username'] = [
          '#type' => 'textfield',
          '#default_value' => isset($chosen[$name]['username']) ? $chosen[$name]['username'] : '',
          // Hide the username for share URLs where it isn't needed.
          // Otherwise it is a required field.
          '#states' => [
            'visible' => [":input[name='follow']" => ['value' => 1]],
            'required' => [
              ":input[name='follow']" => ['value' => 1],
              ":input[name='chosen[$name][enabled]']" => ['checked' => TRUE],
            ],
          ],
        ];
      }
      else {
        $form['chosen'][$name]['username'] = [];
      }
      $form['chosen'][$name]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $name]),
        '#title_display' => 'invisible',
        '#default_value' => isset($chosen[$name]['weight']) ? $chosen[$name]['weight'] : 0,
        '#delta' => 20,
        '#attributes' => ['class' => ['item-row-weight']],
      ];
    }

    // Appearance settings stored as an array ready to pass to the library code.
    $appearance = $config->get('appearance');
    $form['appearance'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => $this->t('Appearance'),
    ];
    $form['appearance']['size'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Size'),
      '#size' => 5,
      '#default_value' => $appearance['size'],
      '#description' => $this->t('Size, as a proportion of default size set in CSS.'),
    ];
    $form['appearance']['shrink'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum size'),
      '#size' => 5,
      '#default_value' => $appearance['shrink'],
      '#description' => $this->t('Minimum size to shrink buttons to, as a proportion of original size.'),
    ];
    $form['appearance']['regrow'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Extra row size'),
      '#size' => 5,
      '#default_value' => $appearance['regrow'],
      '#description' => $this->t('Maximum size of buttons after they have been forced to split onto extra rows of buttons, as a proportion of original size.'),
    ];
    $form['appearance']['minRows'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Minimum rows'),
      '#size' => 5,
      '#default_value' => $appearance['minRows'],
      '#description' => $this->t('Minimum number of rows of buttons.  Set to a large value to create vertical layout.'),
    ];
    $form['appearance']['maxRows'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Maximum rows'),
      '#size' => 5,
      '#default_value' => $appearance['maxRows'],
      '#description' => $this->t('Maximum number of rows of buttons.  If more rows would be needed, instead the labels are hidden.  Set to a large value to keep labels if at all possible.'),
    ];
    $form['appearance']['prefixReserve'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix reserved width'),
      '#size' => 5,
      '#default_value' => $appearance['prefixReserve'],
      '#description' => $this->t('Proportion of total width reserved for prefix to be inline.'),
    ];
    $form['appearance']['prefixHide'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix maximum width'),
      '#size' => 5,
      '#default_value' => $appearance['prefixHide'],
      '#description' => $this->t('Maximum prefix width as a proportion of total width before hiding prefix.'),
    ];
    $form['appearance']['alignRight'] = [
      '#type' => 'checkbox',
      '#title' => t('Right-align buttons'),
      '#size' => 5,
      '#default_value' => $appearance['alignRight'],
      '#description' => t('By default, buttons are left-aligned, with any padding added on the right.  Enable this to right-align, and instead pad on the left.'),
    ];

    $form['prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix text before the buttons'),
      '#default_value' => $config->get('prefix'),
      '#description' => $this->t('Put this text before the buttons.  For example "Follow us" or "Share this page".'),
    ];
    $form['image_tokens'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tokens to use to find images'),
      '#default_value' => $config->get('image_tokens'),
      '#description' => $this->t('Enter one or more tokens, separated by |.  These tokens will be tried in turn to determine the image to use in buttons.
        The default value is @default which you can adapt to pick other fields or as desired.', ['@default' => RRSSB_DEFAULT_IMAGE_TOKEN]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();

    foreach ($values['chosen'] as $name => $settings) {
      if ($settings['enabled']) {
        if ($values['follow'] && isset($settings['username']) && !$settings['username']) {
          $form_state->setErrorByName("chosen[$name][username]", $this->t('You must set the username to use "Follow" button for @button', ['@button' => $name]));
        }
        // If a button is enabled where there is no URL, we don't count that
        // as an error, just don't show the button (@see rrssb_settings).
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    // Clear cached CSS.
    rrssb_cache_flush();

    // @@ Need to clear the cached block and the node field?
    return $result;
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exists($id) {
    $entity = $this->entityTypeManager->getStorage('rrssb_button_set')->getQuery()->condition('id', $id)->execute();
    return (bool) $entity;
  }

}
