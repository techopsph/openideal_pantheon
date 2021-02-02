<?php

namespace Drupal\route_condition\Plugin\Condition;

use Drupal\Core\Condition\ConditionInterface;
use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Route' condition.
 *
 * @Condition(
 *   id = "route",
 *   label = @Translation("Route"),
 * )
 */
class RouteCondition extends ConditionPluginBase implements ConditionInterface, ContainerFactoryPluginInterface {

  /**
   * The CurrentRouteMatch service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructs a Route condition plugin.
   *
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The CurrentRouteMatch service.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(CurrentRouteMatch $current_route_match, array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('current_route_match'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['routes' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Routes'),
      '#default_value' => $this->configuration['routes'],
      '#description' => $this->t("Specify route names. Enter one route per line. The '*' character is a wildcard. An example route is %canonical-wildcard for every entity's canonical view. Prepend the ~ character (tilde) to exclude the route.", [
        '%canonical-wildcard' => 'entity.*.canonical',
      ]),
    ];
    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['routes'] = $form_state->getValue('routes');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    $routes = array_map('trim', explode("\n", $this->configuration['routes']));
    $routes = implode(', ', $routes);
    if (!empty($this->configuration['negate'])) {
      return $this->t('Do not return true on the following routes: @routes', ['@routes' => $routes]);
    }
    return $this->t('Return true on the following routes: @routes', ['@routes' => $routes]);
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {
    // Convert routes to lowercase.
    $routes = mb_strtolower($this->configuration['routes']);
    $routes = str_replace(["\r\n", "\r"], "\n", $routes);
    $routes = explode("\n", $routes);
    if (!$routes) {
      return TRUE;
    }

    $current_route = $this->currentRouteMatch->getCurrentRouteMatch();
    $current_route_name = $current_route->getRouteName();

    foreach ($routes as $route) {
      $negate = isset($route[0]) && $route[0] === '~';
      $route = ltrim($route, '~');

      if ($route === $current_route_name || $this->evaluateRouteWildcards($route, $current_route_name)) {
        return !$negate;
      }
    }

    return FALSE;
  }

  /**
   * Evaluate wildcards in route patterns.
   *
   * @param string $route_pattern
   *   The route to evaluate for wildcards.
   * @param string $current_route_name
   *   The current request route name.
   *
   * @return bool
   *   Indication whether the provided route pattern matches the current route.
   */
  protected function evaluateRouteWildcards($route_pattern, $current_route_name) {
    if (strpos($route_pattern, '*') === FALSE) {
      return FALSE;
    }
    $escaped_route_pattern = str_replace('.', '\.', $route_pattern);
    $route_pattern_wildcards = str_replace('*', '.*', $escaped_route_pattern);
    $regex = "{^{$route_pattern_wildcards}$}";

    return (bool) preg_match($regex, $current_route_name);
  }

}
