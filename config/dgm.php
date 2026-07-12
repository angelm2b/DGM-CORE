<?php

declare(strict_types=1);

return [
    // Clave admin para acceder a la documentación de la API en producción.
    // Si queda vacía, el acceso por clave queda deshabilitado (403 en prod).
    'docs_password' => env('DGM_DOCS_PASSWORD', ''),

    // RN-07: días calendario de inactividad para caducar una solicitud.
    'caducidad_dias' => (int) env('DGM_CADUCIDAD_DIAS', 90),

    // Vigencia por defecto de una orden de pago, en días.
    'orden_pago_vigencia_dias' => (int) env('DGM_ORDEN_PAGO_VIGENCIA_DIAS', 15),

    // Reglas de negocio parametrizables.
    'reglas' => [
        // RN-01: prórroga turista. Base 30 días, máximo acumulado 120 días.
        'turista_dias_base' => 30,
        'turista_dias_max_prorroga' => 120,
        // RN-03: antelación mínima (días) para solicitar una renovación.
        'renovacion_antelacion_dias' => 45,
        // RN-04: penalidad por mes de residencia temporal vencida (DOP).
        'penalidad_mes' => '1000.00',
        // RN-10: vigencia mínima del pasaporte respecto a la fecha de solicitud (meses).
        'pasaporte_vigencia_min_meses' => 6,
        // RN-12: solvencia mínima para RT-9 casado con dominicano(a) (DOP).
        'solvencia_minima' => '150000.00',
        // RN-02: recargo por año o fracción a partir de 10 años de sobreestadía (DOP).
        'estadia_recargo_anual' => '5000.00',
        'estadia_anios_recargo_desde' => 10,
        // RN-09: edad de mayoría para el flujo de menores.
        'mayoria_edad' => 18,
    ],

    // Servicio cuya orden de pago se calcula con la tabla de estadía (RN-02)
    // en lugar de tarifas fijas (ver OrdenPagoService).
    'servicio_tasa_estadia' => 'SRV-007',

    // Códigos de servicio a los que aplican reglas de elegibilidad específicas
    // (ver ElegibilidadService::evaluarSolicitud).
    'elegibilidad' => [
        // Renovaciones: RN-03 (antelación) y RN-08 (póliza validada).
        'servicios_renovacion' => ['SRV-002', 'SRV-004'],
        // Cambio de categoría RT-9 → RP-1: RN-05.
        'servicio_cambio_categoria' => 'SRV-003',
    ],

    // Moneda por defecto del sistema.
    'moneda' => 'DOP',
];
