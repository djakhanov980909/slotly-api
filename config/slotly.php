<?php

return [
    // Часовой пояс, в котором специалисты задают рабочие часы
    'timezone' => env('SLOTLY_TIMEZONE', 'Asia/Almaty'),

    // Шаг между возможными началами записи
    'slot_step_minutes' => 30,
];
