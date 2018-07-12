<?php
namespace Airslamit\DumpOrder\Model;

class CSVGenerator {

	protected $_order;
	protected $_filePath;
  protected $_customOrderId;
  protected $_dir;

  public function __construct(\Magento\Framework\Filesystem\DirectoryList $dir) {
    $this->_dir = $dir;
  }

  /**
   * Will return array of CSV headers that are needed for Fishbowl order import.
   * More details at https://www.fishbowlinventory.com/w/files/csv/importGenericSC.html
   * @return string[]
   */
  private function getCSVHeaders()
  {
    $headers = "OrderNumber,OrderDate,OrderTax,OrderShipping,OrderHandling,OrderDiscount,
    OrderNote,OrderShipMethod,OrderCustomerPO,OrderQBClass,OrderLG,ShipFirstName,
    ShipLastName,ShipAddress1,ShipAddress2,ShipCity,ShipState,ShipZip,ShipCountry,
    ShipEmail,ShipPhone,ShipFax,BillFirstName,BillLastName,BillAddress1,BillAddress2,
    BillCity,BillState,BillZip,BillCountry,BillEmail,BillPhone,BillFax,ItemType,
    ItemNumber,ItemPrice,ItemQuantity,ItemUOM,ItemTaxable,ItemQBClass,ItemNote,
    KitItem,ShowItem,PaymentProcess,PaymentTotal,Payment Method,PaymentTransactionID,
    PaymentMisc,PaymentExpirationDate";
    return explode(",", $headers);
  }

  /**
   * Will return array of first 11 values for fields that start with Order* 
   * @return string[]
   */
  private function getOrderData()
  {
    $dataArray = [];
    $dataArray['orderNumber'] = ($this->_customOrderId)? 
                                $this->_customOrderId : $this->_order->getRealOrderId();
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
   * Will return array of values for shipping fields.
   * @return string[]
   */
  private function getShippingData()
  {
    $shippingAddressObj = $this->_order->getShippingAddress();
    $shippingArray = [];
    $shippingArray['shipFirstName'] = $shippingAddressObj->getFirstname();
    $shippingArray['shipLastName'] = $shippingAddressObj->getLastName();
    $shippingAddresses = $shippingAddressObj->getStreet();
    $shippingArray['shipAddress1'] = $shippingAddresses[0];
    if(count($shippingAddresses) > 1){
      $shippingArray['shipAddress2'] = $shippingAddresses[1];
    } else {
      $shippingArray['shipAddress2'] = "";
    }
    $shippingArray['shipCity'] = $shippingAddressObj->getCity();
    $shippingArray['shipState'] = $shippingAddressObj->getRegionCode();
    $shippingArray['shipZip'] = $shippingAddressObj->getPostcode();
    $shippingArray['shipCountry'] = $shippingAddressObj->getCountryId();
    $shippingArray['shipEmail'] = $shippingAddressObj->getEmail();
    $shippingArray['shipPhone'] = $shippingAddressObj->getTelephone();
    $shippingArray['shipFax'] = $shippingAddressObj->getFax();
    return $shippingArray;
  }

  /**
   * Will return array of values for billing fields.
   * @return string[]
   */
  private function getBillingData()
  {
    $billingAddressObj = $this->_order->getBillingAddress();
    $billingArray = [];
    $billingArray['billFirstName'] = $billingAddressObj->getFirstname();
    $billingArray['billLastName'] = $billingAddressObj->getLastName();
    $billingAddresses = $billingAddressObj->getStreet();
    $billingArray['billAddress1'] = $billingAddresses[0];
    $billingArray['billAddress2'] = (count($billingAddresses) > 1) ? 
                                    $billingAddresses[1] : "";
    $billingArray['billCity'] = $billingAddressObj->getCity();
    $billingArray['billState'] = $billingAddressObj->getRegionCode();
    $billingArray['billZip'] = $billingAddressObj->getPostcode();
    $billingArray['billCountry'] = $billingAddressObj->getCountryId();
    $billingArray['billEmail'] = $billingAddressObj->getEmail();
    $billingArray['billPhone'] = $billingAddressObj->getTelephone();
    $billingArray['billFax'] = $billingAddressObj->getFax();
    return $billingArray;
  }

  /**
   * Will return array of values related to single item in order.
   * @param type $item 
   * @return string[]
   */
  private function getItemData($item, $csvKitFile="")
  {
    if ($csvKitFile = ($csvKitFile === "")) {
      $csvKitFile = $this->_dir->getRoot() . '/Product1.csv';
    }
    $isKitItem = (file_exists($csvKitFile)) ? 
                $this->isKitItem($csvKitFile, $item->getSku()) : false;
    $itemDataArray = [];
    $itemDataArray['itemType'] = $isKitItem?"80":"10";
    $itemDataArray['itemNumber'] = $item->getSku();
    $itemDataArray['itemPrice'] = $item->getPrice();
    $itemDataArray['itemQuantity'] = $item->getQtyOrdered();
    $itemDataArray['itemUOM'] = "";
    $itemDataArray['itemTaxable'] = ($item->getTaxAmount() > 0)?"TRUE":"FALSE";
    $itemDataArray['itemQBClass'] = "";
    $itemDataArray['itemNote'] = "";
    $itemDataArray['kitItem'] = $isKitItem?"1":"0";
    $itemDataArray['showItem'] = "0";
    return $itemDataArray;
  }

  /**
   * Will return array of values related to payment fields.
   * @return string[]
   */
  private function getPaymentData(){
    $paymentObject = $this->_order->getPayment();
    $paymentArray = [];
    $paymentArray['paymentProcess'] = "TRUE";
    $paymentArray['paymentTotal'] = $this->_order->getGrandTotal();
    $paymentArray['paymentMethod'] = $paymentObject->getMethod();
    $paymentArray['paymentTransactionId'] = ($paymentArray['paymentMethod'] != "cashondelivery") ? 
                                            $paymentObject->getTransactionId() : "";
    $paymentArray['PaymentMisc'] = "";
    $isCCMethod = $paymentArray['paymentMethod'] != "cashondelivery" && 
                  $paymentArray['paymentMethod'] != "paypal_express" && 
                  $paymentArray['paymentMethod'] != "braintree_paypal" && 
                  $paymentArray['paymentMethod'] != "affirm_gateway";
    if ($isCCMethod) {
      $tmpShortYearArray = str_split(strval($paymentObject->getCcExpYear()));
      $ccExp = $paymentObject->getCcExpMonth()."/".$tmpShortYearArray[2].$tmpShortYearArray[3];
    }
    $paymentArray['paymentExpirationDate'] = ($isCCMethod) ? $ccExp : "";
    return $paymentArray;
  }

  /**
   * Will write dummy item row(example shipping row).
   * @param type $itemType 
   * @param type|string $itemNumber 
   * @param type|string $itemPrice 
   * @return string[]
   */
  private function getDummyItem($itemType, $itemNumber = "", $itemPrice = "")
  {
    return [$itemType,$itemNumber,$itemPrice,"1","","FALSE","","","",""];
  }

  /**
   * Will check if given item is part of Kit.
   * @param type $filename 
   * @param type $sku 
   * @return boolean
   */
  private function isKitItem($filename, $sku) {
    $f = fopen($filename, "r");
    $result = true;
    while ($row = fgetcsv($f)) {
        if (strtolower($row[1]) == strtolower($sku)) {
            	$result = false;
              break;
        }
    }
    fclose($f);
    return $result;
  }

  /**
   * Will write headers and all values to CSV file.
   * @param type $orderHeaders
   * @param type $orderValues 
   * @return type
   */
  private function writeCSVWithHeaders($orderHeaders, $orderValues)
  {
    $handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $orderHeaders);
    fputcsv($handle, $orderValues);
    fclose($handle);
  }

  /**
   * Will write CSV headers to filePath property.
   * @return type
   */
  private function writeHeaders()
  {
    $handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $this->getCSVHeaders());
    fclose($handle);
  }

  /**
   * Will append data to existing CSV file.
   * @param type $values 
   * @return type
   */
  private function appendToCSVFile($values){
  	$handle = fopen($this->_filePath, "a+");
    fputcsv($handle, $values);
    fclose($handle);
  }

  /**
   * Will write headers, merge all values and call function that will write CSV file.
   * @return type
   */
  public function generateCSVFile()
  {
    //Getters for all meta values
    $orderDataArray = $this->getOrderData();
    $shippingDataArray = $this->getShippingData();
    $billingDataArray = $this->getBillingData();
    $paymentDataArray = $this->getPaymentData();


    $this->writeHeaders(); //Will create order CSV file and write headers to it.

    //Will merge all meta data + item data and append it to CSV file.
    foreach ($this->_order->getAllItems() as $key => $item) {
      $itemDataArray = $this->getItemData($item);
      $mergedArray = array_merge($orderDataArray, $shippingDataArray, $billingDataArray, $itemDataArray, $paymentDataArray);
      $this->appendToCSVFile($mergedArray);
    }
    //Populating additonal 3 rows for shipping, subtotal and tax.
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray, 
                          $billingDataArray, $this->getDummyItem("60", "shipping", "0"), $paymentDataArray));
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray,
                          $billingDataArray, $this->getDummyItem("40"), $paymentDataArray));
    $this->appendToCSVFile(array_merge($orderDataArray, $shippingDataArray,
                          $billingDataArray, $this->getDummyItem("70", "Online SalesTax", "0"), $paymentDataArray));
  }

  public function setOrder($order) {
  	$this->_order = $order;
  }

  public function setFilePath($filePath) {
  	$this->_filePath = $filePath;
  }

  public function setCustomOrderId($customOrderId) {
    $this->_customOrderId = $customOrderId;
  }
}
