<?php
/**
 * ---------------------------------------------------------------------
 * models/Usuario.php
 * ---------------------------------------------------------------------
 * Acceso a la tabla `usuarios`: el personal de la panadería
 * (administradores y empleados). Las contraseñas se guardan con
 * password_hash() y se validan con password_verify().
 * ---------------------------------------------------------------------
 */

defined('APP') or exit('Acceso directo no permitido');

class Usuario extends Model
{
    protected static string $tabla = 'usuarios';

    /** Largo mínimo de contraseña para cuentas nuevas o cambios. */
    public const MIN_CLAVE = 6;

    /** Busca por correo incluyendo el nombre del rol (para el login). */
    public static function porCorreo(string $correo): ?array
    {
        $sql = 'SELECT u.*, r.nombre AS rol FROM usuarios u JOIN roles r ON r.id = u.id_rol WHERE u.correo = ?';
        return self::query($sql, [$correo])->fetch() ?: null;
    }

    /** ¿Ya existe ese correo? (opcionalmente ignorando un id al editar). */
    public static function correoExiste(string $correo, int $exceptoId = 0): bool
    {
        return (bool) self::query('SELECT 1 FROM usuarios WHERE correo = ? AND id <> ?', [$correo, $exceptoId])->fetch();
    }

    /** Todo el personal con el nombre del rol y los pedidos que registró. */
    public static function personal(): array
    {
        $sql = 'SELECT u.*, r.nombre AS rol,
                       (SELECT COUNT(*) FROM pedidos p WHERE p.id_empleado = u.id) AS pedidos_atendidos
                  FROM usuarios u JOIN roles r ON r.id = u.id_rol
                 ORDER BY u.id_rol, u.nombre';
        return self::query($sql)->fetchAll();
    }
}
