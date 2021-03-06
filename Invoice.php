<?php

/*
 * Invoice class file
 */
namespace Moneybird;

use Moneybird\Domainmodel\AbstractModel;
use Moneybird\Mapper\Mapable;
use Moneybird\Invoice\Detail\ArrayObject as DetailArray;
use Moneybird\Invoice\Payment\ArrayObject as PaymentArray;
use Moneybird\Invoice\History\ArrayObject as HistoryArray;
use Moneybird\Payment\AbstractPayment;
use Moneybird\Invoice\Service as InvoiceService;

/**
 * Invoice
 */
class Invoice extends
AbstractModel implements
Mapable, Storable, Sendable, PdfDocument, Payable
{

    protected $address1;
    protected $address2;
    protected $attention;
    protected $city;
    protected $companyName;
    protected $conceptId;
    protected $contactId;
    protected $contactName;
    protected $country;
    protected $createdAt;
    protected $currency;
    protected $customerId;
    protected $daysOpen;
    protected $description;
    protected $discount;
    protected $dueDateInterval;
    protected $email;
    protected $exchangeRate;
    protected $firstname;
    protected $id;
    protected $invoiceDate;
    protected $invoiceEmail;
    protected $invoiceEmailReminder;
    protected $invoiceHash;
    protected $invoiceId;
    protected $invoiceProfileId;
    protected $invoiceProfileVersionId;
    protected $language;
    protected $lastname;
    protected $name;
    protected $originalEstimateId;
    protected $originalInvoiceId;
    protected $payUrl;
    protected $poNumber;
    protected $pricesAreInclTax;
    protected $recurringTemplateId;
    protected $revision;
    protected $sendMethod;
    protected $sepaActive;
    protected $showCustomerId;
    protected $showTax;
    protected $showTaxNumber;
    protected $state;
    protected $taxNumber;
    protected $totalPaid;
    protected $totalPriceExclTax;
    protected $totalPriceInclTax;
    protected $totalTax;
    protected $totalUnpaid;
    protected $updatedAt;
    protected $url;
    protected $zipcode;
    protected $details;
    protected $history;
    protected $payments;
    protected $pdfUrl;
    protected $_readonlyAttr = array(
        'conceptId',
        'contactName',
        'createdAt',
        'daysOpen',
        'email',
        'exchangeRate',
        'id',
        'invoiceEmail',
        'invoiceEmailReminder',
        'invoiceHash',
        'invoiceProfileVersionId',
        'name',
        'originalEstimateId',
        'originalInvoiceId',
        'payUrl',
        'recurringTemplateId',
        'revision',
        'sendMethod',
        'state',
        'totalPaid',
        'totalPriceExclTax',
        'totalPriceInclTax',
        'totalTax',
        'totalUnpaid',
        'updatedAt',
        'url',
        'history',
        'payments',
        'pdfUrl',
    );
    protected $_requiredAttr = array(
        array('contactId', 'companyName', 'firstname', 'lastname'),
    );

    /**
     * Construct a new invoice
     *
     * @param array $data
     * @param Contact $contact
     * @param bool $isDirty new data is dirty, defaults to true
     */
    public function __construct(array $data = array(), Contact $contact = null, $isDirty = true)
    {
        parent::__construct();
        if ($contact !== null) {
            $this->setContact($contact, $isDirty);
        }
        $this->setData($data, $isDirty);
    }

    /**
     * Set Id
     * @param int $value
     * @throws InvalidIdException
     */
    protected function setIdAttr($value)
    {
        if (!is_null($value) && !preg_match('/^[0-9]+$/D', $value)) {
            throw new InvalidIdException('Invalid id: ' . $value);
        }

        $this->id = $value;
    }

    /**
     * Set url
     * @param string $value
     */
    protected function setUrlAttr($value = null)
    {
        if (!is_null($value)) {
            $this->url = $value;
            $this->pdfUrl = $value . '.pdf';
        }
    }

    /**
     * Set details
     * @param DetailArray $value
     * @param bool $isDirty new value is dirty, defaults to true
     */
    protected function setDetailsAttr(DetailArray $value = null, $isDirty = true)
    {
        if (!is_null($value)) {
            $this->details = $value;
            $this->setDirtyState($isDirty, 'details');
        }
    }

    /**
     * Set payments
     * @param PaymentArray $value
     * @param bool $isDirty new value is dirty, defaults to true
     */
    protected function setPaymentsAttr(PaymentArray $value = null, $isDirty = true)
    {
        if (!is_null($value)) {
            $this->payments = $value;
            $this->setDirtyState($isDirty, 'payments');
        }
    }

    /**
     * Set history
     * @param HistoryArray $value
     * @param bool $isDirty new value is dirty, defaults to true
     */
    protected function setHistoryAttr(HistoryArray $value = null, $isDirty = true)
    {
        if (!is_null($value)) {
            $this->history = $value;
            $this->setDirtyState($isDirty, 'history');
        }
    }

    /**
     * Initialize vars 
     */
    protected function _initVars()
    {
        $this->details = new DetailArray();
        $this->history = new HistoryArray();
        $this->payments = new PaymentArray();
        return parent::_initVars();
    }

    /**
     * Send the invoice
     * @param Service $service
     * @param string $method Send method (email|hand|post); default: email
     * @param type $email Address to send to; default: contact e-mail
     * @param type $message
     * @return self
     */
    public function send(Service $service, $method = 'email', $email = null, $message = null)
    {
        return $this->reload(
                $service->send($this, $method, $email, $message)
        );
    }

    /**
     * Mark the invoice as sent
     * @param InvoiceService $service
     * @return self
     */
    public function markAsSent(InvoiceService $service)
    {
        return $this->send($service, 'hand');
    }

    /**
     * Send a reminder for the invoice
     * @param InvoiceService $service
     * @param string $method Send method (email|hand|post); default: email
     * @param type $email Address to send to; default: contact e-mail
     * @param type $message
     * @return self
     * @throws InvalidStateException
     */
    public function remind(InvoiceService $service, $method = 'email', $email = null, $message = null)
    {
        if ($this->state == 'draft') {
            throw new InvalidStateException('Send invoice before reminding');
        }
        return $this->reload(
                $service->remind($this, $method, $email, $message)
        );
    }

    /**
     * Register a payment for the invoice
     * @param Service $service
     * @param AbstractPayment $payment
     * @return self
     * @throws InvalidStateException
     */
    public function registerPayment(Service $service, AbstractPayment $payment)
    {
        if ($this->state == 'draft') {
            throw new InvalidStateException('Send invoice before register payments');
        }
        return $this->reload(
                $service->registerPayment($this, $payment)
        );
    }

    /**
     * Get the raw PDF content
     * @param Service $service
     * @return string
     * @throws InvalidStateException
     */
    public function getPdf(Service $service)
    {
        if ($this->state == 'draft') {
            throw new InvalidStateException('Send invoice before requesting PDF document');
        }
        return $service->getPdf($this);
    }

    /**
     * Copy info from contact to invoice
     *
     * @access public
     * @param Contact $contact
     * @param bool $isDirty new data is dirty, defaults to true
     * @return self
     */
    public function setContact(Contact $contact, $isDirty = true)
    {
        $this->contactId = $contact->id;
        $this->setDirtyState($isDirty, 'contactId');
        $properties = array(
            'address1',
            'address2',
            'attention',
            'city',
            'companyName',
            'country',
            'customerId',
            'firstname',
            'lastname',
            'zipcode',
        );
        foreach ($properties as $property) {
            $this->$property = $contact->$property;
            $this->setDirtyState($isDirty, $property);
        }
        return $this;
    }

    /**
     * Deletes an invoice
     * @param Service $service
     */
    public function delete(Service $service)
    {
        $service->delete($this);
    }

    /**
     * Updates or inserts an invoice
     * @param Service $service
     * @return self
     * @throws NotValidException
     */
    public function save(Service $service)
    {
        if (!$this->validate()) {
            throw new NotValidException('Unable to validate invoice');
        }

        return $this->reload(
                $service->save($this)
        );
    }

    /**
     * Copy the invoice
     * @param array $filter
     * @return self
     */
    public function copy(array $filter = array())
    {
        return parent::copy(array(
                'invoiceDate',
                'invoiceId',
            ));
    }

    /**
     * Copy the invoice to a credit
     * @return self
     */
    public function createCredit()
    {
        $copy = $this->copy();
        $detailsCopy = new $this->details;
        foreach ($copy->details as $detail) {
            $detail->setData(array(
                'amount' => $detail->amount * -1
            ));
            $detailsCopy->append($detail);
        }
        $copy->setData(array(
            'details' => $detailsCopy
        ));
        return $copy;
    }

    /**
     * Settle the payments
     *
     * @param Service $service
     * @param Payable $invoice
     * @param bool $sendEmail
     * @throws InvalidStateException
     * @throws UnableToSettleException
     */
    public function settle(Service $service, Payable $invoice, $sendEmail = false)
    {
        return $service->settle($this, $invoice, $sendEmail);
    }

    /**
     * Validate object
     * @return bool
     */
    protected function validate()
    {
        return count($this->details) > 0 && parent::validate();
    }
}
