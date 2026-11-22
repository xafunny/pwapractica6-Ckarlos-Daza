<?php
/**
 * ---------------------------------------------------------------------
 * controllers/AuthController.php
 * ---------------------------------------------------------------------
 * Inicio y cierre de sesión del personal.
 *   auth/login  GET muestra el formulario, POST valida credenciales
 *   auth/salir  POST cierra la sesión
 * No hay registro público: las cuentas las crea el Administrador.
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class AuthController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear temporalmente. */
    private const MAX_INTENTOS = 5;
    private const BLOQUEO_SEG  = 60;

    public function login(): void
    {
        if (Auth::check()) redirect('inicio/index');

        $error = '';
        $correo = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $correo = mb_strtolower(post('correo'));
            $clave  = $_POST['contrasena'] ?? '';
            $intentos = $_SESSION['intentos_login'] ?? ['n' => 0, 't' => 0];

            if (!csrf_valido()) {
                $error = 'La sesión expiró, vuelve a intentarlo.';
            } elseif ($intentos['n'] >= self::MAX_INTENTOS && time() - $intentos['t'] < self::BLOQUEO_SEG) {
                $error = 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.';
            } elseif ($correo === '' || $clave === '') {
                $error = 'Completa todos los campos.';
            } else {
                $u = Usuario::porCorreo($correo);
                if (!$u || !password_verify($clave, $u['contrasena'])) {
                    // Mensaje genérico: no revela si el correo existe.
                    $_SESSION['intentos_login'] = ['n' => $intentos['n'] + 1, 't' => time()];
                    $error = 'Correo o contraseña incorrectos.';
                } elseif (!$u['activo']) {
                    $error = 'Tu cuenta está desactivada. Comunícate con el administrador.';
                } else {
                    unset($_SESSION['intentos_login']);
                    Auth::login($u);
                    flash('success', '¡Buen día, ' . $u['nombre'] . '!');
                    redirect('inicio/index');
                }
            }
        }

        $this->view('auth/login', ['titulo' => 'Iniciar sesión', 'error' => $error, 'correo' => $correo]);
    }

    public function salir(): void
    {
        $this->soloPost();
        Auth::logout();
        session_start(); // nueva sesión solo para el mensaje flash
        flash('info', 'Sesión cerrada correctamente.');
        redirect('auth/login');
    }
}
