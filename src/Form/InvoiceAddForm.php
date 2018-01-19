<?php

namespace Drupal\commerce_order_invoice\Form;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Form\CustomerFormTrait;
use Drupal\commerce_order_invoice\Entity\InvoiceInterface;
use Drupal\commerce_order_invoice\Entity\InvoiceItem;
use Drupal\commerce_order_invoice\OrderResolverService;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the order add form.
 */
class InvoiceAddForm extends FormBase {

  use CustomerFormTrait;

  /**
   * The order storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $invoiceStorage;

  /**
   * The store storage.
   *
   * @var \Drupal\Core\Entity\Sql\SqlContentEntityStorage
   */
  protected $storeStorage;

  /**
   * The order resolver service.
   *
   * @var \Drupal\commerce_order_invoice\OrderResolverService
   */
  private $orderResolver;

  /**
   * Constructs a new OrderAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_order_invoice\OrderResolverService $orderResolverService
   *   The order resolver.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, OrderResolverService $orderResolverService) {
    $this->invoiceStorage = $entity_type_manager->getStorage('invoice');
    $this->storeStorage = $entity_type_manager->getStorage('commerce_store');
    $this->orderResolver = $orderResolverService;
  }

  /**
   * Gets the order resolver service.
   *
   * In exceptional cases the resolver is not created when this class is called.
   *
   * @return \Drupal\commerce_order_invoice\OrderResolverService
   *   The order resolver service.
   */
  private function getOrderResolver() {
    if (NULL === $this->orderResolver) {
      return new OrderResolverService();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('commerce_order_invoice.order_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_order_invoice_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Skip building the form if there are no available stores.
    $store_query = $this->storeStorage->getQuery();
    if ($store_query->count()->execute() === 0) {
      $link = Link::createFromRoute('Add a new store.', 'entity.commerce_store.add_page');
      $form['warning'] = [
        '#markup' => $this->t("Invoices can't be created until a store has been added. @link", ['@link' => $link->toString()]),
      ];
      return $form;
    }

    $form['store_id'] = [
      '#type' => 'commerce_entity_select',
      '#title' => $this->t('Store'),
      '#target_type' => 'commerce_store',
      '#required' => TRUE,
    ];
    $form = $this->buildCustomerForm($form, $form_state);

    if ($order = $this->orderResolver->getOrderFromRoute()) {
      if (!$form_state->hasValue('uid')) {
        $form['customer']['uid']['#default_value'] = $order->getCustomer();
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->submitCustomerForm($form, $form_state);

    $values = $form_state->getValues();

    $invoice_data = [
      'mail' => $values['mail'],
      'uid' => [$values['uid']],
      'store_id' => [$values['store_id']],
    ];

    if ($order = $this->getOrderResolver()->getOrderFromRoute()) {
      $invoice_data['order_id'] = $order;
    }

    /** @var \Drupal\commerce_order_invoice\Entity\InvoiceInterface $invoice */
    $invoice = $this->invoiceStorage->create($invoice_data);
    $this->copyOrderDataIfPresent($invoice);
    $invoice->save();

    // Redirect to the edit form to complete the invoice.
    $form_state->setRedirect('entity.invoice.edit_form', ['invoice' => $invoice->id()]);
  }

  /**
   * Copies the order data to the invoice if there is one.
   *
   * @param \Drupal\commerce_order_invoice\Entity\InvoiceInterface $invoice
   *   The invoice entity.
   *
   * @todo: This needs to be rewritten.
   */
  private function copyOrderDataIfPresent(InvoiceInterface $invoice) {
    if ($order = $this->getOrderResolver()->getOrderFromRoute()) {
      $order_fields = $order->getFields(FALSE);
      $invoice_fields = $invoice->getFields(FALSE);

      $matching_fields = array_intersect_key($order_fields, $invoice_fields);

      // Exclude some fields that are already configured or should be ignored.
      unset(
        $matching_fields['order_id'],
        $matching_fields['store_id'],
        $matching_fields['mail'],
        $matching_fields['uid'],
        $matching_fields['uuid'],
        $matching_fields['changed'],
        $matching_fields['placed'],
        $matching_fields['completed'],
        $matching_fields['locked'],
        $matching_fields['created']
      );

      $this->copyOrderItemsToInvoice($order, $invoice);

      // Copy remaining data to the invoice.
      foreach ($matching_fields as $field_index => $field_data) {
        $invoice->set($field_index, $order->get($field_index)->getValue());
      }
    }
  }

  /**
   * Copies order items to the invoice.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Order.
   * @param \Drupal\commerce_order_invoice\Entity\InvoiceInterface $invoice
   *   Invoice.
   *
   * @todo: This code needs to be improved.
   */
  private function copyOrderItemsToInvoice(OrderInterface $order, InvoiceInterface $invoice) {
    $items_order = $order->get('order_items')->referencedEntities();
    $items_invoice = [];

    $needed_fields = [
      'type',
      'purchased_entity',
      'title',
      'quantity',
      'unit_price',
      'overridden_unit_price',
      'adjustments',
      'data',
    ];

    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    foreach ($items_order as $item) {
      foreach ($needed_fields as $field) {
        if ('unit_price' === $field) {
          $priceField = $item->get($field);
          if (!$priceField->isEmpty()) {
            $values[$field] = [
              'number' => $priceField->first()->getValue()['number'],
              'currency_code' => $priceField->first()
                ->getValue()['currency_code'],
            ];
          }
        }
        else {
          $values[$field] = $item->get($field)->getString();
        }
      }
      if ($values) {
        $invoice_item = InvoiceItem::create($values);
        $invoice_item->save();
        $items_invoice[] = $invoice_item->id();
        unset($values);
      }
    }
    $invoice->set('invoice_items', $items_invoice);
  }

}
