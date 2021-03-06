<?php

/**
 * @authors: Nicolaas [at] Sunny Side Up .co.nz
 * @package: ecommerce
 * @sub-package: model
 * @inspiration: Silverstripe Ltd, Jeremy
 **/
class OrderStep_SecurityCheck extends OrderStep implements OrderStepInterface
{

    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanCancel' => 0,
        'CustomerCanPay' => 0,
        'Name' => 'Security Check for Order',
        'Code' => 'SECURITY_CHECK',
        'ShowAsInProcessOrder' => 1,
        'HideStepFromCustomer' => 1
    );

    /**
     * The OrderStatusLog that is relevant to the particular step.
     *
     * @var string
     */
    protected $relevantLogEntryClassName = 'OrderStatusLog_SecurityCheck';

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        return $fields;
    }

    /**
     *initStep:
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        $logCount = $this->RelevantLogEntries($order)->count();
        if($logCount) {
            //do nothing
        } else {
            $className = $this->relevantLogEntryClassName;
            $object = $className::create();
            $object->OrderID = $order->ID;
            $object->write();
        }
        return true;
    }

    private static $_passed = null;

    /**
     *doStep:
     * should only be able to run this function once
     * (init stops you from running it twice - in theory....)
     * runs the actual step.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if run correctly.
     **/
    public function doStep(Order $order)
    {
        if(self::$_passed !== null) {
            return self::$_passed;
        }
        if ($entry = $this->RelevantLogEntry($order)) {
            self::$_passed = $entry->pass();
            return self::$_passed;
        }
    }

    /**
     *nextStep:
     * returns the next step (after it checks if everything is in place for the next step to run...).
     *
     * @see Order::doNextStatus
     *
     * @param Order $order
     *
     * @return OrderStep | Null (next step OrderStep object)
     **/
    public function nextStep(Order $order)
    {
        if ($this->doStep($order)) {
            return parent::nextStep($order);
        }

        return;
    }

    private static $_my_order = null;

    /**
     * Allows the opportunity for the Order Step to add any fields to Order::getCMSFields.
     *
     * @param FieldList $fields
     * @param Order     $order
     *
     * @return FieldList
     **/
    public function addOrderStepFields(FieldList $fields, Order $order)
    {
        $fields = parent::addOrderStepFields($fields, $order);
        $title = _t('OrderStep.MUST_ACTION_SECURITY_CHECKS', ' ... To move this order to the next step you have to carry out a bunch of security checks.');
        $field = $order->getOrderStatusLogsTableFieldEditable('OrderStatusLog_SecurityCheck', $title);
        $logEntry = $this->RelevantLogEntry($order);
        $link = '/admin/sales/Order/EditForm/field/Order/item/'.$order->ID.'/ItemEditForm/field/OrderStatusLog_SecurityCheck/item/'.$logEntry->ID.'/edit';
        $button = EcommerceCMSButtonField::create(
            'OrderStatusLog_SecurityCheck_Button',
            $link,
            'Open Security Checks'
        );
        $fields->addFieldsToTab('Root.Next', array($button, $field), 'ActionNextStepManually');

        return $fields;
    }


    /**
     * For some ordersteps this returns true...
     *
     * @return bool
     **/
    protected function hasCustomerMessage()
    {
        return false;
    }

    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return 'Make sure that the Order is safe to proceed';
    }
}
