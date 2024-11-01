<?php
namespace NappsSmartCollections\Events;

use NappsSmartCollections\Repository\SmartCollectionRepository;

class UpdateCreatedXDays {

    /*
    *   Retrieve all smartcollections that have a condition in last x_days
    *   in order to update their products list in case the amount of days does not meet the product
    */
    public function handle() {

        $repository = new SmartCollectionRepository();
        $smartCollections = $repository->getSmartCollectionsWithXDaysDefined();

        foreach($smartCollections as $smartCollection) {

            WC()->queue()->add(
                Jobs::UPDATE_PRODUCTS_ON_SMARTCOLLECTION,
                array(
                    'term_id' => $smartCollection->id,
                )
            );
            
        }

    }
}