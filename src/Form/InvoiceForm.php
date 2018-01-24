<?php

namespace Drupal\commerce_invoice\Form;

use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for Invoice edit forms.
 *
 * @ingroup commerce_invoice
 */
class InvoiceForm extends ContentEntityForm {

  /**
   * The entity being used by this form.
   *
   * @var \Drupal\commerce_invoice\Entity\InvoiceInterface
   */
  protected $entity;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a ContentEntityForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, DateFormatterInterface $date_formatter) {
    $this->dateFormatter = $date_formatter;
    parent::__construct($entity_manager, $entity_type_bundle_info, $time);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $invoice = $this->entity;

    $form['#tree'] = TRUE;
    $form['#theme'] = 'commerce_order_edit_form';
    $form['#attached']['library'][] = 'commerce_order/form';

    // Changed must be sent to the client, for later overwrite error checking.
    $form['changed'] = [
      '#type' => 'hidden',
      '#default_value' => $invoice->getChangedTime(),
    ];

    $last_saved = $this->dateFormatter->format($invoice->getChangedTime(), 'short');
    $form['advanced'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['entity-meta']],
      '#weight' => 99,
    ];
    $form['meta'] = [
      '#attributes' => ['class' => ['entity-meta__header']],
      '#type' => 'container',
      '#group' => 'advanced',
      '#weight' => -100,
      'state' => [
        '#type' => 'html_tag',
        '#tag' => 'h3',
        '#value' => $invoice->isLocked() ? t('Confirmed invoice') : t('Draft invoice'),
        '#attributes' => [
          'class' => 'entity-meta__title',
        ],
        // Hide the rendered state if there's a widget for it.
        '#access' => empty($form['store_id']),
      ],
      'date' => NULL,
      'changed' => $this->fieldAsReadOnly($this->t('Last saved'), $last_saved),
    ];
    $form['customer'] = [
      '#type' => 'details',
      '#title' => t('Customer information'),
      '#group' => 'advanced',
      '#open' => TRUE,
      '#attributes' => [
        'class' => ['order-form-author'],
      ],
      '#weight' => 91,
    ];

    if ($placed_time = $invoice->getPlacedTime()) {
      $date = $this->dateFormatter->format($placed_time, 'short');
      $form['meta']['date'] = $this->fieldAsReadOnly($this->t('Placed'), $date);
    }
    // Show the order's store only if there are multiple available.
    $store_query = $this->entityManager->getStorage('commerce_store')
      ->getQuery();
    $store_count = $store_query->count()->execute();
    if ($store_count > 1) {
      $store_link = $invoice->getStore()->toLink()->toString();
      $form['meta']['store'] = $this->fieldAsReadOnly($this->t('Store'), $store_link);
    }
    // Move uid/mail widgets to the sidebar, or provide read-only alternatives.
    if (isset($form['uid'])) {
      $form['uid']['#group'] = 'customer';
    }
    else {
      $user_link = $invoice->getCustomer()->toLink()->toString();
      $form['customer']['uid'] = $this->fieldAsReadOnly($this->t('Customer'), $user_link);
    }
    if (isset($form['mail'])) {
      $form['mail']['#group'] = 'customer';
    }
    elseif (!empty($invoice->getEmail())) {
      $form['customer']['mail'] = $this->fieldAsReadOnly($this->t('Contact email'), $invoice->getEmail());
    }
    // All additional customer information should come after uid/mail.
    $form['customer']['ip_address'] = $this->fieldAsReadOnly($this->t('IP address'), $invoice->getIpAddress());

    $form['actions']['submit']['#value'] = t('Save');

    if (!$this->entity->isLocked()) {
      $form['confirm'] = [
        '#type' => 'checkbox',
        '#title' => t('Confirm this invoice.'),
        '#weight' => 99,
        '#description' => t('After confirming an invoice it can no longer be changed.'),
      ];
    }
    else {
      unset($form['actions']['submit'], $form['actions']['delete']);
      $form['actions']['#markup'] = t('This invoice is confirmed and can no longer be modified.');
    }

    return $form;
  }

  /**
   * Builds a read-only form element for a field.
   *
   * @param string $label
   *   The element label.
   * @param string $value
   *   The element value.
   *
   * @return array
   *   The form element.
   */
  protected function fieldAsReadOnly($label, $value) {
    return [
      '#type' => 'item',
      '#wrapper_attributes' => [
        'class' => [
          Html::cleanCssIdentifier(strtolower($label)),
          'container-inline',
        ],
      ],
      '#markup' => '<h4 class="label inline">' . $label . '</h4> ' . $value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    if ($entity->isLocked()) {
      drupal_set_message(t('Invoice is confirmed and can no longer be modified'), 'error');
      return FALSE;
    }

    if ($form_state->getValue('confirm', FALSE)) {
      $invoiceNumberGenerationService = \Drupal::service('commerce_invoice.invoice_number_generation_service');
      $invoice_number = $invoiceNumberGenerationService->generateAndSetInvoiceNumber();

      $entity->set('invoice_number', $invoice_number);

      $entity->lock();
    }

    $status = parent::save($form, $form_state);

    if ($status === SAVED_NEW) {
      drupal_set_message($this->t('Created the %label Invoice.', [
        '%label' => $entity->label(),
      ]));
    }

    $form_state->setRedirect('entity.invoice.canonical', [
      'invoice' => $this->entity->id(),
    ]);

    return NULL;
  }

}
