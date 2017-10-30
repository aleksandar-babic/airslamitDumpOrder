<?php
namespace Airslamit\DumpOrder\Observer\Sales\Order;

use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{
  protected $_workingDir;

  public function __construct(\Magento\Framework\Filesystem\DirectoryList $workingDir)
  {
    $this->_workingDir = $workingDir;
  }

  public function execute(\Magento\Framework\Event\Observer $observer)
  {
    $order = $observer->getEvent()->getOrder();
	file_put_contents($this->_workingDir->getPath('var')."/log/orders.log", $order->getId());
  }
}