<?php

return [
    // Navegación
    'nav.dashboard'       => 'Panel de control',
    'nav.courses'         => 'Cursos',
    'nav.profile'         => 'Perfil',
    'nav.settings'        => 'Configuración',
    'nav.support'         => 'Soporte',
    'nav.logout'          => 'Cerrar sesión',
    'nav.login'           => 'Iniciar sesión',
    'nav.register'        => 'Registrarse',

    // Autenticación
    'auth.email'          => 'Correo electrónico',
    'auth.password'       => 'Contraseña',
    'auth.name'           => 'Nombre completo',
    'auth.login'          => 'Iniciar sesión',
    'auth.register'       => 'Crear cuenta',
    'auth.forgot'         => '¿Olvidaste tu contraseña?',
    'auth.logout'         => 'Cerrar sesión',

    // Errores de autenticación
    'auth.error.fields_required'     => 'Todos los campos son obligatorios.',
    'auth.error.invalid_credentials' => 'Correo electrónico o contraseña incorrectos.',
    'auth.error.invalid_email'       => 'Por favor ingresá un correo electrónico válido.',
    'auth.error.password_too_short'  => 'La contraseña debe tener al menos 8 caracteres.',
    'auth.error.email_taken'         => 'Este correo electrónico ya está registrado.',
    'auth.error.generic'             => 'Ocurrió un error. Por favor, intentá de nuevo.',

    // Cursos
    'course.continue'     => 'Continuar aprendiendo',
    'course.start'        => 'Comenzar curso',
    'course.locked'       => 'Bloqueado',
    'course.unlock'       => 'Desbloquear este curso',
    'course.lessons'      => ':count lecciones',
    'course.hours'        => ':count horas de video',
    'course.instructor'   => 'Instructor',
    'course.about'        => 'Acerca de este curso',
    'course.content'      => 'Contenido del curso',
    'course.sections'     => ':count secciones',
    'course.completed'    => ':percent% completado',
    'course.sources'      => 'Fuentes y adjuntos',
    'course.search'       => 'Buscar lecciones...',

    // Reproductor
    'lesson.complete'     => 'Completar y continuar',
    'lesson.prev'         => 'Anterior',
    'lesson.next'         => 'Siguiente',
    'lesson.subtitles'    => 'Subtítulos',
    'lesson.speed'        => 'Velocidad de reproducción',
    'lesson.back'         => 'Volver al panel de control',

    // Comentarios
    'comment.title'       => 'Comentarios',
    'comment.placeholder' => 'Escribe un comentario...',
    'comment.submit'      => 'Publicar comentario',
    'comment.reply'       => 'Responder',
    'comment.pending'     => 'En espera de aprobación',
    'comment.approved'    => 'Aprobado',
    'comment.load_more'   => 'Cargar más comentarios',

    // Configuración
    'settings.title'      => 'Configuración',
    'settings.theme'      => 'Tema',
    'settings.theme_light' => 'Claro',
    'settings.theme_dark' => 'Oscuro',
    'settings.language'   => 'Idioma',
    'settings.font_size'  => 'Tamaño de fuente',
    'settings.subtitles'  => 'Mostrar subtítulos por defecto',
    'settings.speed'      => 'Velocidad de reproducción por defecto',
    'settings.save'       => 'Guardar configuración',
    'settings.saved'      => 'Configuración guardada',

    // Panel de control
    'dashboard.greeting'  => '¡Hola, :name!',
    'dashboard.progress'  => 'Tu progreso',
    'dashboard.continue'  => 'Continuar viendo',
    'dashboard.enrolled'  => 'Mis cursos',
    'dashboard.no_courses' => 'Todavía no estás inscrito en ningún curso.',
    'dashboard.expires'   => 'Acceso hasta: :date',

    // Admin
    'admin.courses'       => 'Cursos',
    'admin.users'         => 'Usuarios',
    'admin.grant_access'  => 'Dar acceso',
    'admin.revoke_access' => 'Revocar acceso',
    'admin.moderation'    => 'Moderación de comentarios',
    'admin.approve'       => 'Aprobar',
    'admin.reject'        => 'Rechazar',
    'admin.payments'      => 'Pagos',
    'admin.pending'       => ':count pendientes',

    // Pagos
    'payment.title'       => 'Obtener acceso',
    'payment.stripe'      => 'Pagar con tarjeta',
    'payment.paypal'      => 'Pagar con PayPal',
    'payment.success'     => '¡Pago exitoso! Ya tenés acceso a :course.',
    'payment.failed'      => 'El pago falló. Por favor, intentá de nuevo.',
    'payment.access_for'  => 'Acceso por 1 año',

    // Acceso / inscripción
    'access.locked_title'   => 'Esta lección está bloqueada',
    'access.locked_message' => 'Comprá el curso o pedile al administrador que te dé acceso.',
    'access.expired_title'  => 'Tu acceso ha expirado',
    'access.expired_message' => 'Renová tu acceso para continuar aprendiendo.',
    'access.renew'          => 'Renovar acceso',

    // General
    'general.save'        => 'Guardar',
    'general.cancel'      => 'Cancelar',
    'general.delete'      => 'Eliminar',
    'general.edit'        => 'Editar',
    'general.back'        => 'Volver',
    'general.loading'     => 'Cargando...',
    'general.error'       => 'Ocurrió un error. Por favor, intentá de nuevo.',
    'general.success'     => '¡Listo!',
    'general.search'      => 'Buscar',
];
