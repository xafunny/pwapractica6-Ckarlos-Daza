-- =====================================================================
--  PANADERÍA LA ESPIGA - Sistema de gestión de una panadería
--  Base de datos MySQL / MariaDB  (importar desde phpMyAdmin)
-- ---------------------------------------------------------------------
--  Proyecto final - Programación Web Avanzada (Ejercicio 33)
--
--  Cambios respecto a la base de datos sugerida en el enunciado:
--   * Los clientes no inician sesión (el enunciado solo tiene los roles
--     Administrador y Empleado), por eso `clientes` no guarda contraseña.
--     El personal está en `usuarios` relacionado con `roles`.
--   * Un pedido puede tener VARIOS productos: `pedidos` (cabecera) +
--     `detalle_pedido` (líneas). Hay dos tipos de pedido:
--       - Mostrador: venta inmediata, queda entregada al registrarla.
--       - Encargo: se prepara para una fecha y hora de entrega.
--   * `categorias` para organizar los productos.
--   * `finanzas` guarda movimientos (ingresos y gastos) con su concepto;
--     el balance se calcula sumando, en vez de guardarse a mano.
--   * Llaves foráneas reales, nombres sin tildes y DECIMAL para dinero.
--
--  USO LOCAL (XAMPP / Laragon):
--    1. En phpMyAdmin crear la base de datos `panaderia` (utf8mb4_unicode_ci)
--    2. Seleccionarla y en la pestaña "Importar" subir este archivo.
--
--  Usuarios de prueba (contraseña de todos: 123456):
--    admin@panaderia.com     -> Administrador
--    empleado@panaderia.com  -> Empleado
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS finanzas;
DROP TABLE IF EXISTS detalle_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS roles;

SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- ROLES
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (id, nombre) VALUES
  (1, 'Administrador'),
  (2, 'Empleado');

-- ---------------------------------------------------------------------
-- USUARIOS: personal que inicia sesión (administradores y empleados)
--   contrasena guarda el HASH de password_hash() (bcrypt)
-- ---------------------------------------------------------------------
CREATE TABLE usuarios (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  nombre     VARCHAR(50)  NOT NULL,
  apellido   VARCHAR(50)  NOT NULL DEFAULT '',
  correo     VARCHAR(100) NOT NULL UNIQUE,
  contrasena VARCHAR(255) NOT NULL,
  id_rol     INT          NOT NULL,
  telefono   VARCHAR(15)  NULL,
  activo     TINYINT(1)   NOT NULL DEFAULT 1,
  creado_en  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_usuarios_roles FOREIGN KEY (id_rol) REFERENCES roles(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CLIENTES: personas que compran o hacen encargos (no inician sesión)
-- ---------------------------------------------------------------------
CREATE TABLE clientes (
  id        INT AUTO_INCREMENT PRIMARY KEY,
  nombre    VARCHAR(50)  NOT NULL,
  apellido  VARCHAR(50)  NOT NULL DEFAULT '',
  telefono  VARCHAR(15)  NOT NULL,
  correo    VARCHAR(100) NULL,
  direccion VARCHAR(255) NULL,
  creado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- CATEGORIAS de productos (icono = nombre de Bootstrap Icons)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id     INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(40) NOT NULL UNIQUE,
  icono  VARCHAR(30) NOT NULL DEFAULT 'basket'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PRODUCTOS: panes, pasteles, dulces y bebidas
--   activo = 0 lo oculta del punto de venta sin borrarlo
-- ---------------------------------------------------------------------
CREATE TABLE productos (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  id_categoria INT          NOT NULL,
  nombre       VARCHAR(60)  NOT NULL,
  descripcion  VARCHAR(255) NOT NULL DEFAULT '',
  precio       DECIMAL(8,2) NOT NULL,
  stock        INT          NOT NULL DEFAULT 0,
  activo       TINYINT(1)   NOT NULL DEFAULT 1,
  CONSTRAINT fk_productos_categoria FOREIGN KEY (id_categoria) REFERENCES categorias(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- PEDIDOS (cabecera)
--   id_cliente NULL = venta al consumidor final (sin datos)
--   fecha_entrega solo aplica a los encargos
-- ---------------------------------------------------------------------
CREATE TABLE pedidos (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  id_cliente    INT           NULL,
  id_empleado   INT           NULL,
  tipo          ENUM('Mostrador','Encargo') NOT NULL DEFAULT 'Mostrador',
  fecha         DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  fecha_entrega DATETIME      NULL,
  notas         VARCHAR(255)  NULL,
  total         DECIMAL(10,2) NOT NULL DEFAULT 0,
  estado        ENUM('Pendiente','Preparando','Listo','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  INDEX idx_pedidos_estado (estado),
  INDEX idx_pedidos_entrega (fecha_entrega),
  CONSTRAINT fk_pedidos_cliente  FOREIGN KEY (id_cliente)  REFERENCES clientes(id) ON DELETE SET NULL,
  CONSTRAINT fk_pedidos_empleado FOREIGN KEY (id_empleado) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- DETALLE_PEDIDO: productos de cada pedido con el precio del momento
-- ---------------------------------------------------------------------
CREATE TABLE detalle_pedido (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  id_pedido       INT           NOT NULL,
  id_producto     INT           NOT NULL,
  cantidad        INT           NOT NULL,
  precio_unitario DECIMAL(8,2)  NOT NULL,
  subtotal        DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_detalle_pedido   FOREIGN KEY (id_pedido)   REFERENCES pedidos(id)   ON DELETE CASCADE,
  CONSTRAINT fk_detalle_producto FOREIGN KEY (id_producto) REFERENCES productos(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- FINANZAS: al entregar un pedido se registra su ingreso (id_pedido).
-- Los gastos (harina, gas, sueldos...) se registran a mano.
-- ---------------------------------------------------------------------
CREATE TABLE finanzas (
  id         INT AUTO_INCREMENT PRIMARY KEY,
  fecha      DATE          NOT NULL,
  tipo       ENUM('Ingreso','Gasto') NOT NULL,
  concepto   VARCHAR(120)  NOT NULL,
  monto      DECIMAL(10,2) NOT NULL,
  id_pedido  INT           NULL UNIQUE,
  creado_por INT           NULL,
  INDEX idx_finanzas_fecha (fecha),
  CONSTRAINT fk_finanzas_pedido  FOREIGN KEY (id_pedido)  REFERENCES pedidos(id)  ON DELETE SET NULL,
  CONSTRAINT fk_finanzas_usuario FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
-- DATOS DE PRUEBA (contraseña de los usuarios: 123456)
-- =====================================================================
INSERT INTO usuarios (id, nombre, apellido, correo, contrasena, id_rol, telefono) VALUES
  (1, 'Admin',  'Espiga', 'admin@panaderia.com',    '$2y$12$haz3HPC8t19/8XiKclrNCeWzAKWt3FKyKuZ0p9UCRwCqi1hJ2dB6C', 1, '0970000001'),
  (2, 'Rosa',   'Vélez',  'empleado@panaderia.com', '$2y$12$haz3HPC8t19/8XiKclrNCeWzAKWt3FKyKuZ0p9UCRwCqi1hJ2dB6C', 2, '0970000002');

INSERT INTO clientes (id, nombre, apellido, telefono, correo, direccion) VALUES
  (1, 'Gabriela', 'Torres',  '0991112233', 'gabriela@correo.com', 'Calle Bolívar 210'),
  (2, 'Jorge',    'Castro',  '0992223344', NULL,                  'Av. Las Américas y 10 de Agosto'),
  (3, 'Lucía',    'Herrera', '0993334455', 'lucia@correo.com',    NULL);

INSERT INTO categorias (id, nombre, icono) VALUES
  (1, 'Panes',    'basket'),
  (2, 'Pasteles', 'cake2'),
  (3, 'Dulces',   'cookie'),
  (4, 'Bebidas',  'cup-hot');

INSERT INTO productos (id, id_categoria, nombre, descripcion, precio, stock) VALUES
  (1,  1, 'Pan de agua',          'Pan crujiente horneado cada mañana',      0.15, 200),
  (2,  1, 'Pan integral',         'Con harina integral y semillas',          0.25, 80),
  (3,  1, 'Cachito',              'Pan de mantequilla en forma de cuerno',   0.20, 120),
  (4,  1, 'Baguette',             'Baguette de 40 cm',                        1.25, 15),
  (5,  2, 'Torta de chocolate',   'Torta entera para 12 porciones',         18.00, 3),
  (6,  2, 'Tres leches',          'Porción individual',                       2.50, 20),
  (7,  2, 'Cheesecake de mora',   'Porción individual',                       2.75, 4),
  (8,  3, 'Empanada de viento',   'Rellena de queso, con azúcar',             0.60, 40),
  (9,  3, 'Rosca de anís',        'Rosca tradicional',                        0.35, 60),
  (10, 3, 'Galletas de avena',    'Paquete de 6 unidades',                    1.50, 0),
  (11, 4, 'Café pasado',          'Taza de café de la casa',                  0.90, 100),
  (12, 4, 'Chocolate caliente',   'Taza grande',                              1.25, 100);

-- Pedidos de ejemplo (fechas relativas a hoy)
INSERT INTO pedidos (id, id_cliente, id_empleado, tipo, fecha, fecha_entrega, notas, total, estado) VALUES
  (1, NULL, 2, 'Mostrador', NOW() - INTERVAL 2 DAY,   NULL,                                   NULL,                         2.70,  'Entregado'),
  (2, 1,    2, 'Encargo',   NOW() - INTERVAL 3 DAY,   NOW() - INTERVAL 1 DAY,                 'Escribir "Feliz cumpleaños"', 18.00, 'Entregado'),
  (3, 2,    2, 'Encargo',   NOW() - INTERVAL 1 DAY,   TIMESTAMP(CURDATE(), '17:00:00'),        '50 panes para una reunión',   10.00, 'Preparando'),
  (4, 3,    2, 'Encargo',   NOW() - INTERVAL 2 HOUR,  TIMESTAMP(CURDATE() + INTERVAL 1 DAY, '09:00:00'), NULL,            5.25,  'Pendiente'),
  (5, NULL, 2, 'Mostrador', NOW() - INTERVAL 30 MINUTE, NULL,                                 NULL,                         2.15,  'Entregado');

INSERT INTO detalle_pedido (id_pedido, id_producto, cantidad, precio_unitario, subtotal) VALUES
  (1, 1, 10, 0.15, 1.50), (1, 11, 1, 0.90, 0.90), (1, 9, 1, 0.30, 0.30),
  (2, 5, 1, 18.00, 18.00),
  (3, 1, 50, 0.15, 7.50), (3, 3, 10, 0.25, 2.50),
  (4, 6, 1, 2.50, 2.50),  (4, 7, 1, 2.75, 2.75),
  (5, 3, 5, 0.20, 1.00),  (5, 12, 1, 1.15, 1.15);

INSERT INTO finanzas (fecha, tipo, concepto, monto, id_pedido, creado_por) VALUES
  (CURDATE() - INTERVAL 9 DAY,  'Gasto',   'Compra de harina (5 quintales)', 120.00, NULL, 1),
  (CURDATE() - INTERVAL 5 DAY,  'Gasto',   'Gas para el horno',               25.00, NULL, 1),
  (CURDATE() - INTERVAL 2 DAY,  'Ingreso', 'Pedido #1',                        2.70, 1,    2),
  (CURDATE() - INTERVAL 2 DAY,  'Ingreso', 'Ventas del día (caja)',           185.00, NULL, 1),
  (CURDATE() - INTERVAL 1 DAY,  'Ingreso', 'Pedido #2',                       18.00, 2,    2),
  (CURDATE() - INTERVAL 1 DAY,  'Ingreso', 'Ventas del día (caja)',           160.00, NULL, 1),
  (CURDATE(),                   'Ingreso', 'Pedido #5',                        2.15, 5,    2),
  (CURDATE(),                   'Gasto',   'Mantequilla y huevos',             38.50, NULL, 1);
