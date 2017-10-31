<?php
namespace Airslamit\DumpOrder\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
  protected $_workingDir;
  protected $_ordersDir;

  /**
   * Constructor will setup working directory and order directory variables, create directory to keep orders CSV files if it does not already exist.
   * @param \Magento\Framework\Filesystem\DirectoryList $workingDir 
   * @return type
   */
  public function __construct(\Magento\Framework\Filesystem\DirectoryList $workingDir)
  {
    $this->_workingDir = $workingDir;
    $this->_ordersDir = $this->_workingDir->getPath('var')."/orders/";

    if (!file_exists($this->_ordersDir)) {
      mkdir($this->_ordersDir, 0777, true);
    }
  }

  /**
   * This function will write all headers that are required for Fishbowl order import (more at https://www.fishbowlinventory.com/w/files/csv/importGenericSC.html).
   * @param type $file 
   * @return type
   */
  public function writeCSVHeaders($file) {
    $headers = "OrderNumber,OrderDate,OrderTax,OrderShipping,OrderHandling,OrderDiscount,OrderNote,OrderShipMethod,OrderCustomerPO,OrderQBClass,OrderLG,ShipFirstName,ShipLastName,ShipAddress1,ShipAddress2,ShipCity,ShipState,ShipZip,ShipCountry,ShipEmail,ShipPhone,ShipFax,BillFirstName,BillLastName,BillAddress1,BillAddress2,BillCity,BillState,BillZip,BillCountry,BillEmail,BillPhone,BillFax,ItemType,ItemNumber,ItemPrice,ItemQuantity,ItemUOM,ItemTaxable,ItemQBClass,ItemNote,KitItem,ShowItem,PaymentProcess,PaymentTotal,Payment Method,PaymentTransactionID,PaymentMisc,PaymentExpirationDate";
    file_put_contents($file, $headers);
  }

  /**
   * This function will generate CSV order file that is compataible with Fishbowl order import.
   * @param type $order 
   * @return type
   */
  public function generateOutput($order) {
    $filePath = $this->_ordersDir.$order->getId();
    $this->writeCSVHeaders($filePath);
  }

  /**
   * This function is observer for event sales_order_save_after
   * @param \Magento\Framework\Event\Observer $observer 
   * @return type
   */
  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $order = $observer->getEvent()->getOrder();
	  $this->generateOutput($order);
  }
}