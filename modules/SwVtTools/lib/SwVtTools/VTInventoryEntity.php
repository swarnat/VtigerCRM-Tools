<?php
namespace SwVtTools;

use VTEntity;
use \PearDatabase;

/**
 * Created by JetBrains PhpStorm.
 * User: Stefan Warnat <support@stefanwarnat.de>
 * Date: 29.03.13
 * Time: 16:46
 */
class VTInventoryEntity extends VTEntity {

    protected $_listitems = false;
    protected $_changedProducts = false;
    protected $_shTax = array();
    protected $_groupTax = array();
    protected $_shipTaxes = array();
    protected $_shippingCost = 0;
    protected $_currencyData = false;
    protected $_isInventory = true;
    protected $_currencyID = "";

    protected $_saveRequest = array(
        "ajxaction" => "DETAILVIEW"
    );

    protected function __construct($module_name, $id) {
        parent::__construct($module_name, $id);
    }

    public function set($key, $value) {
        if($key == "currency_id") {
            $this->_currencyID = $value;
            #if($this->get("currency_id") != $value) {
                #$this->_data["currency_id"] = $value;
                #$this->_changed = true;
            #}
        } else {
            parent::set($key, $value);
        }
    }
    public function clearData() {
        parent::clearData();
        $this->_currencyData = false;
    }

    public function exportInventory() {
        $this->_loadProducts();

        return array(
            "listitems" => $this->_listitems,
            "shTax" => $this->_shTax,
            "groupTax" => $this->_groupTax,
            "shipTaxes" => $this->_shipTaxes,
            "shippingCost" => $this->_shippingCost
        );
    }
    public function importInventory($values) {
        $this->_listitems = $values["listitems"];
        $this->_shTax = $values["shTax"];
        $this->_groupTax = $values["groupTax"];
        $this->_shipTaxes = $values["shipTaxes"];
        $this->_shippingCost = $values["shippingCost"];

        $this->_changedProducts = true;
    }

    public function setGroupTaxes($taxes) {
        if($this->_listitems === false) {
            $this->_loadProducts();
        }

        $this->_groupTax = $taxes;
    }
    public function setShipTaxes($taxes) {
        if($this->_listitems === false) {
            $this->_loadProducts();
        }

        $this->_shipTaxes = $taxes;
    }
    public function setShippingCost($cost) {
        if($this->_listitems === false) {
            $this->_loadProducts();
        }

        $this->_shippingCost = $cost;
    }

    public function getData() {
        parent::getData();

        if(empty($this->_data["currency_id"]) && !empty($this->_id)) {
            $this->_currencyData = getInventoryCurrencyInfo($this->getModuleName(), $this->_id);

            foreach($this->_currencyData as $key => $value) {
                $this->_data[$key] = $value;
            }
            $db = PearDatabase::getInstance();
            $sql = "select id from vtiger_ws_entity WHERE name = 'Currency'";
            $res = $db->query($sql);
            $wstabid = $db->query_result($res, 0, "id");

            $this->_data["currency_id"] = $wstabid."x".$this->_data["currency_id"];
            $this->_currencyID = $this->_data["currency_id"];
        }
        #var_dump($this->_listitems);

        return $this->_data;
    }
    private function _loadProducts() {
        $focus = $this->getInternalObject();

       $this->_listitems = array();

        $products = getAssociatedProducts($this->_moduleName, $focus);

        $final_details = $products[1]["final_details"];
        if(isset($final_details)) {
            if(count($this->_groupTax) == 0) {
                $taxes = array();
                if($final_details["taxtype"] == "group") {
                    foreach($final_details["taxes"] as $tax) {
                        $taxes[$tax["taxname"]."_group_percentage"] = $tax["percentage"];
                    }
                    $this->setGroupTaxes($taxes);
                }
            }

            if(count($this->_shipTaxes) == 0) {
                $taxes = array();
                foreach($final_details["sh_taxes"] as $tax) {
                    $taxes[substr($tax["taxname"], 2)."_sh_percent"] = $tax["percentage"];
                }
                $this->setShipTaxes($taxes);
            }
            $this->setShippingCost($final_details["shipping_handling_charge"]);
        }

        if(is_array($products) && count($products) > 0) {
            foreach($products as $index => $product) {
                if(empty($product["hdnProductId".$index])) {
                    continue;
                }

                $productArray = array(
                    "productid" => $product["hdnProductId".$index],
                    "quantity" => $product["qty".$index],
                    "comment" => $product["comment".$index],
                    "description" => $product["productDescription".$index],
                    "unitprice" => $product["listPrice".$index],
                    "discount_percent" => $product["discount_percent".$index],
                    "discount_amount" => $product["discount_amount".$index],
                );

                if(!empty($product["taxes"]) && is_array($product["taxes"])) {
                    foreach($product["taxes"] as $key => $value) {
                        $productArray[$value["taxname"]] = $value["percentage"];
                    }
                }

                $this->_listitems[] = $productArray;
            }
        }
    }

    public function getProducts() {

    }

    public function addProduct($productid, $description, $comment, $quantity, $unitprice, $discount_percent = 0, $discount_amount = 0, $tax = array()) {
        global $adb;

        if($quantity == 0) {
            return 0;
        }
        if($this->_listitems === false) {
            $this->_loadProducts();
        }

        $this->_changedProducts = true;

        $productArray = array(
            "productid" => intval($productid),
            "quantity" => floatval($quantity),
            "comment" => $comment,
            "description" => $description,
            "unitprice" => floatval($unitprice),
            "discount_percent" => $discount_percent,
            "discount_amount" => $discount_amount
        );

        foreach($tax as $key => $value) {
            $productArray["tax".$key] = $value;
        }

        $this->_listitems[] = $productArray;
    }

    public function getCurrencyId() {
        $this->getData();
        #var_dump($this->_currencyID);
        return $this->_currencyID;
    }

    public function save() {
        $adb = PearDatabase::getInstance();

        if(!empty($this->_id)) {
            $this->_changedProducts = true;
            if($this->_listitems === false) {
                $this->_loadProducts();
            }
        }

        parent::save();

        if(!empty($this->_currencyID)) {
            $currency_id = $this->_currencyID;
        } else {
            $currency_id = false;
        }

        #$internalObject = $this->getInternalObject();

        $this->clearData();

        if($this->_changedProducts === true) {
            $taxtype = $this->get("hdnTaxType");
            $adjustment = 0;
            $shipping_handling_charge = 0;

            $availTaxes = getAllTaxes();
            $counter = 1;
            $_REQUEST['totalProductCount'] = count($this->_listitems);
            $_REQUEST['taxtype'] = $taxtype;

            $_REQUEST['subtotal'] = 0;

            foreach($this->_listitems as $product) {
                $_REQUEST["deleted".$counter] = 0;
                $_REQUEST["hdnProductId".$counter] = $product["productid"];
                $_REQUEST["productDescription".$counter] = $product["description"];
                $_REQUEST["qty".$counter] = $product["quantity"];
                $_REQUEST["listPrice".$counter] = $product["unitprice"];
                $_REQUEST["comment".$counter] = $product["comment"];

                if(!empty($product["discount_percent"])) {
                    $_REQUEST["discount_type".$counter] = "percentage";
                    $product["discount_amount"] = 0;
                } elseif(!empty($product["discount_amount"])) {
                    $_REQUEST["discount_type".$counter] = "amount";
                    $product["discount_percent"] = 0;
                }

                $_REQUEST["discount_percentage".$counter] = $product["discount_percent"];
                $_REQUEST["discount_amount".$counter] = $product["discount_amount"];

                $productTotal = 0;
                $taxValue = 0;

                $productTotal += $_REQUEST["qty".$counter] * $_REQUEST["listPrice".$counter] - $_REQUEST["discount_amount".$counter];

                if($product["discount_percent"] > 0) {
                    $productTotal = ($productTotal * (1 - ($product["discount_percent"] / 100)));
                }

                foreach($availTaxes as $tax) {
                    if(isset($product["tax".$tax["taxid"]]) && !empty($product["tax".$tax["taxid"]])) {
                        if($taxtype == "group") {
    #                        $tax_name = $tax['taxname'];
    #                        $request_tax_name = $tax_name."_group_percentage";
    #                        $_REQUEST[$request_tax_name] = $product["tax".$tax["taxid"]];
                        } else {
                            $tax_name = $tax['taxname'];
                            $request_tax_name = $tax_name."_percentage".$counter;

                            $_REQUEST[$request_tax_name] = $product["tax".$tax["taxid"]];

                            $tmpTaxValue = $productTotal * ($product["tax".$tax["taxid"]] / 100);
                            $taxValue += $tmpTaxValue;
                            $productTotal += $tmpTaxValue;

                        }

                    } else {
                        $tax_name = $tax['taxname'];
                        $request_tax_name = $tax_name."_percentage".$counter;

                        $_REQUEST[$request_tax_name] = 0;
                    }
                }

                $_REQUEST['subtotal'] += $productTotal;

                $counter++;
            }

            $_REQUEST['discount_percentage_final'] = $this->get("hdnDiscountPercent");
            $_REQUEST['discount_percentage_final'] = floatval($_REQUEST['discount_percentage_final']);

            $_REQUEST['discount_amount_final'] = $this->get("hdnDiscountAmount");
            $_REQUEST['discount_amount_final'] = floatval($_REQUEST['discount_amount_final']);

            $_REQUEST['discount_type_final'] = !empty($_REQUEST['discount_percentage_final'])?'percentage':'amount';

            $_REQUEST['total'] = $_REQUEST['subtotal'];

            if($_REQUEST['discount_type_final'] == "amount") {
                $_REQUEST['total'] -= $_REQUEST['discount_amount_final'];
            } elseif($_REQUEST['discount_type_final'] == "percentage") {
                $_REQUEST['total'] -= ($_REQUEST['total'] * ($_REQUEST['discount_percentage_final'] / 100));
            }

            $globalTaxValue = 0;

            if($taxtype == "group") {
                foreach($availTaxes as $tax) {
                    $tax_name = $tax['taxname'];
                    $request_tax_name = $tax_name."_group_percentage";
                    $_REQUEST[$request_tax_name] = isset($this->_groupTax[$request_tax_name])?$this->_groupTax[$request_tax_name]:0;

                    $tmpTaxValue = $_REQUEST['total'] * ($_REQUEST[$request_tax_name] / 100);
                    $globalTaxValue += $tmpTaxValue;
                }

                $_REQUEST['total'] += $globalTaxValue;
            }

            $_REQUEST['shipping_handling_charge'] = $this->_shippingCost;

            $shipTaxValue = 0;

            foreach($availTaxes as $tax) {
                $tax_name = $tax['taxname'];
                $request_tax_name = $tax_name."_sh_percent";
                $_REQUEST["sh".$request_tax_name] = isset($this->_shipTaxes[$request_tax_name])?$this->_shipTaxes[$request_tax_name]:0;

                $tmpTaxValue = $_REQUEST['shipping_handling_charge'] * ($_REQUEST["sh".$request_tax_name] / 100);
                $shipTaxValue += $tmpTaxValue;
            }

            $_REQUEST['total'] += $shipTaxValue + $_REQUEST['shipping_handling_charge'];

            $_REQUEST['adjustment'] = floatval($this->get("txtAdjustment"));

            $_REQUEST['total'] += $_REQUEST['adjustment'];

            $intObject = $this->getInternalObject();
            $intObject->mode = "edit";

            ob_start();
                saveInventoryProductDetails($intObject, $this->getModuleName());
            ob_end_clean();
        }

        if(!empty($currency_id)) {
            if(strpos($currency_id, "x") !== false) {
                $parts = explode("x", $currency_id);
                $currency_id = $parts[1];
            } else {
                $currency_id = $currency_id;
            }

            $cur_sym_rate = getCurrencySymbolandCRate($currency_id);
            $conversion_rate = $cur_sym_rate['rate'];

            $intObject = $this->getInternalObject();
            $update_query = "update ".$intObject->table_name." set currency_id = ?, conversion_rate = ? WHERE ".$intObject->table_index." = ?";
            $update_params = array($currency_id, $conversion_rate, $this->_id);

            #var_dump($update_query, $update_params);
            $adb->pquery($update_query, $update_params);

        }

    // Update the currency id and the conversion rate for the sales order


    }

    /**
     * @return \Inventory_Record_Model
     */
    public function getModel() {
        if($this->_isDummy) {
            return false;
        }

        $this->save();

        return \Inventory_Record_Model::getInstanceById($this->_id, $this->_moduleName);
    }
}
