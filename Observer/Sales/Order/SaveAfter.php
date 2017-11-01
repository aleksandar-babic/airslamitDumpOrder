<?php
namespace Airslamit\DumpOrder\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
  protected $_workingDir;
  protected $_ordersDir;
  protected $_order;
  protected $_orderFilePath;

  /**
   * Constructor will setup working directory and order directory variables, create directory to keep orders CSV files if it does not already exist.
   * @param \Magento\Framework\Filesystem\DirectoryList $workingDir 
   * @return type
   */
  public function __construct(\Magento\Framework\Filesystem\DirectoryList $workingDir)
  {
    $this->_workingDir = $workingDir;
    $this->_ordersDir = $this->_workingDir->getPath("var")."/orders/";

    if (!file_exists($this->_ordersDir)) {
      mkdir($this->_ordersDir, 0777, true);
    }
  }

  /**
   * Will return array of CSV headers that are needed for Fishbowl order import.
   * More details at https://www.fishbowlinventory.com/w/files/csv/importGenericSC.html
   * @return type
   */
  private function getCSVHeaders()
  {
    $headers = "OrderNumber,OrderDate,OrderTax,OrderShipping,OrderHandling,OrderDiscount,OrderNote,OrderShipMethod,OrderCustomerPO,OrderQBClass,OrderLG,ShipFirstName,ShipLastName,ShipAddress1,ShipAddress2,ShipCity,ShipState,ShipZip,ShipCountry,ShipEmail,ShipPhone,ShipFax,BillFirstName,BillLastName,BillAddress1,BillAddress2,BillCity,BillState,BillZip,BillCountry,BillEmail,BillPhone,BillFax,ItemType,ItemNumber,ItemPrice,ItemQuantity,ItemUOM,ItemTaxable,ItemQBClass,ItemNote,KitItem,ShowItem,PaymentProcess,PaymentTotal,Payment Method,PaymentTransactionID,PaymentMisc,PaymentExpirationDate";
    return explode(",", $headers);
  }

  /**
   * Will return array of first 11 values for fields that start with Order* 
   * @return type
   */
  private function getOrderData()
  {
    $dataArray = [];
    $dataArray['orderNumber'] = $this->_order->getId();
    $dataArray['orderDate'] = date("m/d/Y", strtotime($this->_order->getCreatedAt()));
    $dataArray['orderTax'] = "";
    $dataArray['orderShipping'] = "";
    $dataArray['orderHandling'] = "";
    $dataArray['orderDiscount'] = "";
    $dataArray['orderNote'] = "";
    $dataArray['orderShipMethod'] = "";
    $dataArray['orderCustomerPO'] = $dataArray['orderNumber'];
    $dataArray['orderQBClass'] = "";
    $dataArray['orderLG'] = "";
    return $dataArray;
  }

  /**
   * Will write headers and all values to CSV file.
   * @param type $orderHeaders 
   * @param type $orderValues 
   * @return type
   */
  private function writeCSV($orderHeaders, $orderValues){
    $handle = fopen($this->_orderFilePath, "a+");
    fputcsv($handle, $orderHeaders);
    fputcsv($handle, $orderValues);
    fclose($handle);
  }

  /**
   * Will get headers, merge all values and call function that will write CSV file.
   * @return type
   */
  private function generateOutput()
  {
    $this->_orderFilePath = $this->_ordersDir.$this->_order->getId();

    $orderHeaders = $this->getCSVHeaders();
    $orderData = $this->getOrderData();
    $mergedArray = array_merge($orderData);

    $this->writeCSV($orderHeaders,$mergedArray);
  }

  /**
   * Will observe event sales_order_save_after.
   * @param \Magento\Framework\Event\Observer $observer 
   * @return type
   */
  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $this->_order = $observer->getEvent()->getOrder();
	  $this->generateOutput();
  }
}