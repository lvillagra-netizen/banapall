<?php

return [
    'auth' => [
        'password' => 'La contraseña es incorrecta.',
        'throttle' => 'Demasiados intentos de acceso. Inténtalo de nuevo en :seconds segundos.',
        'user' => 'No encontramos ninguna cuenta con estos datos.',
        'failed' => 'Estas credenciales no coinciden con nuestros registros.',
    ],
    'password' => [
        'reset' => '¡Tu contraseña ha sido restablecida!',
        'sent' => '¡Te hemos enviado por correo el enlace para restablecer tu contraseña!',
        'throttled' => 'Por favor, espera antes de intentar de nuevo.',
        'token' => 'Este token de restablecimiento de contraseña no es válido.',
        'user' => 'No encontramos ninguna cuenta con esa dirección de correo electrónico.',
    ],
    'verification' => [
        'hash_invalid' => 'El token de verificación de la dirección de correo no es válido.',
        'throttled' => 'Por favor, espera antes de intentar de nuevo.',
        'passed' => '¡Gracias por verificar tu correo!',
    ],
];
