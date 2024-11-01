<?php

namespace NappsSmartCollections\Models;

class SmartCollectionCondition {
        
    /**
     * target
     *
     * @var string
     */
    public $target;   

    /**
     * compare
     *
     * @var string
     */
    public $compare;   
    
    /**
     * date
     *
     * @var string|null
     */
    public $date;    

    /**
     * attribute
     *
     * @var number|null
     */
    public $attribute;
    
    /**
     * discountAmount
     *
     * @var number|null
     */
    public $discountAmount;
    
    public function __construct($data = null)
    {
        if(!$data) {
            return;
        }

        $this->target = isset($data['target']) ? $data['target'] : 'product_attribute';
        $this->compare = isset($data['compare']) ? $data['compare'] : 'is_equal';
        $this->date = isset($data['date']) ? $data['date'] : null;
        $this->attribute  = isset($data['attribute']) ? intval($data['attribute']) : null;
        $this->discountAmount = isset($data['discount_amount']) ? intval($data['discount_amount']) : null;

    }

}
