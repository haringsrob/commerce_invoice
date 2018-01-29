<?php

namespace Drupal\commerce_invoice\Plugin\Field\FieldFormatter;

use Drupal\commerce_invoice\InvoiceTotalSummaryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'invoice_total_summary' formatter.
 *
 * @FieldFormatter(
 *   id = "invoice_total_summary",
 *   label = @Translation("Invoice total summary"),
 *   field_types = {
 *     "commerce_price",
 *   },
 * )
 */
class InvoiceTotalSummary extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The order total summary service.
   *
   * @var \Drupal\commerce_order\OrderTotalSummaryInterface
   */
  protected $invoiceTotalSummary;

  /**
   * Constructs a new OrderTotalSummary object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\commerce_invoice\InvoiceTotalSummaryInterface $invoice_total_summary
   *   The order total summary service.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, InvoiceTotalSummaryInterface $invoice_total_summary) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->invoiceTotalSummary = $invoice_total_summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('commerce_invoice.invoice_total_summary')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $invoice = $items->getEntity();
    return [
      '#theme' => 'commerce_order_total_summary',
      '#totals' => $this->invoiceTotalSummary->buildTotals($invoice),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $entity_type = $field_definition->getTargetEntityTypeId();
    $field_name = $field_definition->getName();
    return $entity_type === 'commerce_invoice' && $field_name === 'total_price';
  }

}
