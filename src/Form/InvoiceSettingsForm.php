<?php

namespace Drupal\commerce_invoice\Form;

use Drupal\commerce_invoice\InvoiceNumber;
use Drupal\commerce_invoice\InvoiceNumberFormatterInterface;
use Drupal\commerce_invoice\InvoiceNumberGeneratorManager;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\KeyValueStore\KeyValueFactory;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class InvoiceSettingsForm.
 *
 * @ingroup commerce_invoice
 */
class InvoiceSettingsForm extends ConfigFormBase {

  /**
   * The order number generator manager.
   *
   * @var \Drupal\commerce_invoice\InvoiceNumberGeneratorManager
   */
  protected $orderNumberGeneratorManager;

  /**
   * The order number formatter.
   *
   * @var \Drupal\commerce_invoice\InvoiceNumberFormatterInterface
   */
  protected $orderNumberFormatter;

  /**
   * The commerce_invoice keyvalue store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private $keyValueStore;

  /**
   * Constructs a new SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\commerce_invoice\InvoiceNumberGeneratorManager $order_number_generator_manager
   *   The order number generator manager.
   * @param \Drupal\commerce_invoice\InvoiceNumberFormatterInterface $order_number_formatter
   *   The order number formatter.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactory $keyValueFactory
   *   The keyvalue factory
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    InvoiceNumberGeneratorManager $order_number_generator_manager,
    InvoiceNumberFormatterInterface $order_number_formatter,
    KeyValueFactory $keyValueFactory
  ) {
    parent::__construct($config_factory);

    $this->orderNumberGeneratorManager = $order_number_generator_manager;
    $this->orderNumberFormatter = $order_number_formatter;
    $this->keyValueStore = $keyValueFactory->get('commerce_invoice');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.commerce_invoice_number_generator'),
      $container->get('commerce_invoice.invoice_number_formatter'),
      $container->get('keyvalue')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'invoice_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('commerce_invoice.settings')
      ->set(
        'invoice_number_pattern',
        $form_state->getValue('invoice_number_pattern')
      )
      ->set(
        'invoice_number_padding',
        $form_state->getValue('invoice_number_padding')
      )
      ->set(
        'invoice_number_generator',
        $form_state->getValue('invoice_number_generator')
      )
      ->set(
        'invoice_number_start',
        $form_state->getValue('invoice_number_start')
      )
      ->save();

    if (!$this->hasInvoiceNumbers() && $form_state->getValue(
        'invoice_number_start'
      )) {
      $this->keyValueStore->set(
        'last_invoice_number',
        (int) $form_state->getValue('invoice_number_start')
      );
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_invoice.settings');

    $generator_plugins = array_map(
      function ($definition) {
        return sprintf(
          '%s (%s)',
          $definition['label'],
          $definition['description']
        );
      },
      $this->orderNumberGeneratorManager->getDefinitions()
    );

    $form['invoice_number_generator'] = [
      '#type' => 'select',
      '#options' => $generator_plugins,
      '#required' => TRUE,
      '#default_value' => $config->get('invoice_number_generator'),
      '#title' => $this->t('Generator plugin'),
      '#description' => $this->t(
        'Choose the plugin to be used for order number generation.'
      ),
    ];

    $form['invoice_number_padding'] = [
      '#type' => 'number',
      '#default_value' => $config->get('invoice_number_padding'),
      '#min' => 0,
      '#step' => 1,
      '#title' => $this->t('Order number padding'),
      '#description' => $this->t(
        'Pad the order number with leading zeroes. Example: a value of 6 will output order number 52 as 000052.'
      ),
    ];

    $pattern_help_text = $this->t(
      'In addition to the generation method, a
    pattern for the invoice number can be set, e.g. to pre- or suffix the
    calculated number. The placeholder %order_number is replaced with the
    generated number and *must* be included in the pattern. If you are using the
    yearly pattern, the placeholder %year_placeholder must be included as well.
    For the montly pattern, additionally the placeholder %month_placeholder is
    mandatory.',
      [
        '%order_number' => InvoiceNumberFormatterInterface::PATTERN_PLACEHOLDER_INVOICE_NUMBER,
        '%year_placeholder' => InvoiceNumberFormatterInterface::PATTERN_PLACEHOLDER_YEAR,
        '%month_placeholder' => InvoiceNumberFormatterInterface::PATTERN_PLACEHOLDER_MONTH,
      ]
    );

    $form['invoice_number_pattern'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invoice numbering pattern'),
      '#default_value' => $config->get('invoice_number_pattern'),
      '#description' => $pattern_help_text,
    ];

    if (!$this->hasInvoiceNumbers()) {
      $form['invoice_number_start'] = [
        '#type' => 'textfield',
        '#title' => $this->t('The initial invoice number'),
        '#default_value' => $config->get('invoice_number_start'),
        '#description' => $this->t(
          'The initial invoice number, the first invoice created will be 
          the number after the one entered here. Example: 
          if you enter 1, then 2 will be the first invoice.'
        ),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'commerce_invoice.settings',
    ];
  }

  /**
   * Checks if invoices are available.
   *
   * @return bool
   *   TRUE if invoices are available.
   */
  protected function hasInvoiceNumbers() {
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = \Drupal::database()
      ->select('commerce_invoice', 'ci');
    $query->addField('ci', 'invoice_number');
    $result = $query->execute()->fetchAll();
    return count($result) >= 1;
  }

}
