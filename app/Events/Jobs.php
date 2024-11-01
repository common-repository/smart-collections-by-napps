<?php

namespace NappsSmartCollections\Events;

class Jobs {

    /**
     * @var UpdateProductsList
     */
    private $updateProductsList;

    /**
     * @var UpdateCreatedXDays
     */
    private $updateCreatedAtXDays;

    public const UPDATE_PRODUCTS_ON_SMARTCOLLECTION = 'napps_update_products_smartcollection';
    public const SCHEDULE_DAILY_UPDATE_CREATED_AT_X_DAYS = 'napps_update_created_at_x_days';

    public function __construct()
    {
        $this->updateProductsList = new UpdateProductsList();
        $this->updateCreatedAtXDays = new UpdateCreatedXDays();

        add_action( Jobs::UPDATE_PRODUCTS_ON_SMARTCOLLECTION, array($this->updateProductsList, 'handle'), 10, 3 );
        add_action( Jobs::SCHEDULE_DAILY_UPDATE_CREATED_AT_X_DAYS, array($this->updateCreatedAtXDays, 'handle'), 10);
    }

}
