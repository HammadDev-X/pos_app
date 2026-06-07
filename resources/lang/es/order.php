<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma para Órdenes
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan durante las órdenes para diversos
    | mensajes que necesitamos mostrar al usuario. Puedes modificar
    | estas líneas de idioma según los requisitos de tu aplicación.
    |
    */

    //==========================================
    // Mensajes del módulo de Órdenes
    //==========================================
    'title'         => 'Órdenes',
    'submit'        => 'Filtrar',
    'Orders_List'   => 'Lista de Órdenes',

    //==========================================
    // Mensajes del módulo de la tabla
    //==========================================
    'ID'                => 'ID',
    'Customer_Name'     => 'Nombre del Cliente',
    'Total'             => 'Total',
    'Received_Amount'   => 'Monto Recibido',
    'Status'            => 'Estado',
    'To_Pay'            => 'Por Pagar',
    'Created_At'        => 'Creado el',
    'Not_Paid'          => 'No Pagado',
    'Partial'           => 'Parcial',
    'Paid'              => 'Pagado',
    'Change'            => 'Cambio',
    'Actions'           => 'Acciones',

    'partial_payment_success' => 'Pago parcial exitoso',
    'amount_exceeds_balance' => 'El monto excede el saldo',
    'created_successfully' => 'Pedido creado exitosamente',

    'validation' => [
        'amount_decimal' => 'El importe debe usar maximo dos decimales.',
        'amount_min' => 'El importe debe ser al menos cero.',
        'amount_required' => 'El importe recibido es obligatorio.',
        'customer_not_found' => 'El cliente seleccionado no fue encontrado.',
        'order_id_required' => 'El pedido es obligatorio.',
        'order_not_found' => 'El pedido seleccionado no fue encontrado.',
        'payment_method_invalid' => 'El metodo de pago seleccionado no es valido.',
        'payment_method_required' => 'El metodo de pago es obligatorio.',
    ],
];
