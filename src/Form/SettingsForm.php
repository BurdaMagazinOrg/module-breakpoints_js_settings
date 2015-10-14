<?php

/**
 * @file
 * Contains \Drupal\breakpoint_js_settings\Form\SettingsForm.
 */

namespace Drupal\breakpoint_js_settings\Form;

use Drupal\breakpoint\BreakpointManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures ivw settings.
 */
class SettingsForm extends ConfigFormBase {
  /**
   * The token object.
   *
   * @var BreakpointManagerInterface
   */
  protected $breakpointManager = array();

  /**
   * Constructs a \Drupal\breakpoint_js_settings\SettingsForm object.
   *
   * @param ConfigFactoryInterface $config_factory
   *  The factory for configuration objects.
   * @param BreakpointManagerInterface $breakpoint_manager
   *  The token object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, BreakpointManagerInterface $breakpoint_manager) {
    parent::__construct($config_factory);
    $this->breakpointManager = $breakpoint_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('breakpoint.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'breakpoint_js_settings_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $definitions = $this->config('breakpoint_js_settings.definitions');

    $form['min_width'] = array(
      '#type' => 'details',
      '#title' => t('Min-widths for breakpoints'),
      '#open' => TRUE,
      '#description' => t('Define min-witdh for given breakpoint, keep empty if you do not wish to use this breakpoint. The breakpoints are extracted from all definitions found in corresponting *.breakpoint.yml files')
    );

    $breakpoint_default = $definitions->get('breakpoints');
    foreach ($this->breakpointManager->getDefinitions() as $name => $definition) {
      $element_name = str_replace('.', '_', $name) . '_width';
      $default_value = '';
      foreach ($breakpoint_default as $defaults) {
        if ($defaults['breakpoint_name'] == $element_name) {
          $default_value = $defaults['breakpoint_min_width'];
          break;
        }
      }

      $form['min_width'][$element_name] = [
        '#type' => 'textfield',
        '#title' => $name,
        '#size' => 10,
        '#field_suffix' => 'px',
        '#placeholder' => 'min-width',
        '#default_value' => $default_value,
        '#description' => t('Media query for @name is "@query"', array(
          '@name' => $name,
          '@query' => $definition['mediaQuery']
        ))
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $values = $form_state->getValues();

    $breakpoints = [];
    foreach (Element::children($form['min_width']) as $width_child) {
      if ($values[$width_child] !== '') {
        $breakpoints[] = [
          'breakpoint_name' => $width_child,
          'breakpoint_min_width' => $values[$width_child]
        ];
      }
    }
    $config = $this->configFactory()
      ->getEditable('breakpoint_js_settings.definitions');
    $config->set('breakpoints', $breakpoints)->save();

//    $config->set('ad_rubric_default', $values['ad_rubric_default'])
//      ->set('ad_rubric_overridable', $values['ad_rubric_overridable'])
//      ->set('ad_ressort_default', $values['ad_ressort_default'])
//      ->set('ad_ressort_overridable', $values['ad_ressort_overridable'])
//      ->save();
  }


  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'breakpoint_js_settings.settings',
    ];
  }
}
