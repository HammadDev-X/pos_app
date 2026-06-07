<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Orders Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during Orders for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    //==========================================
    // Orders module messages
    //==========================================
    'title'         => 'Orders',
    'submit'        => 'Filter',
    'Orders_List'   => 'Orders List',

    //==========================================
    // Table module messages
    //==========================================
    'ID'                => 'ID',
    'Customer_Name'     => 'Customer Name',
    'Total'             => 'Total',
    'Received_Amount'   => 'Received Amount',
    'Status'            => 'Status',
    'To_Pay'            => 'To Pay',
    'Created_At'        => 'Created At',
    'Not_Paid'          => 'Not Paid',
    'Partial'           => 'Partial',
    'Paid'              => 'Paid',
    'Change'            => 'Change',
    'Actions'           => 'Actions',

    'partial_payment_success' => 'Partial payment success',
    'amount_exceeds_balance' => 'Amount exceeds balance',
    'created_successfully' => 'Order created successfully',

    'validation' => [
        'amount_decimal' => 'The amount must use no more than two decimal places.',
        'amount_min' => 'The amount must be at least zero.',
        'amount_required' => 'The received amount is required.',
        'customer_not_found' => 'The selected customer was not found.',
        'order_id_required' => 'The order is required.',
        'order_not_found' => 'The selected order was not found.',
        'payment_method_invalid' => 'The selected payment method is invalid.',
        'payment_method_required' => 'The payment method is required.',
    ],
];
