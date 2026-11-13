-- 1. Asegurar que la base de datos exista y esté seleccionada
CREATE DATABASE IF NOT EXISTS sistema_inventario;
USE sistema_inventario;

-- 2. Limpieza de tablas viejas (para evitar errores de "ya existe")
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

-- 3. Tabla para el módulo de Login y Seguridad
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(100) NOT NULL,
    usuario VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol VARCHAR(20) NOT NULL
);

-- 4. NUEVA TABLA RAÍZ: Categorías del sistema
CREATE TABLE categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_categoria VARCHAR(50) NOT NULL UNIQUE
);

-- 5. TABLA DEPENDIENTE MODIFICADA: Productos con Llave Foránea
CREATE TABLE productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_producto VARCHAR(100) NOT NULL,
    categoria_id INT NOT NULL,
    stock INT NOT NULL,
    precio DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
);

-- 6. Inserción de los catálogos base (deben insertarse primero)
INSERT INTO categorias (nombre_categoria) VALUES
('Computadoras'),
('Accesorios'),
('Oficina');

-- 7. Inserción de Productos (vinculados usando números enteros)
INSERT INTO productos (nombre_producto, categoria_id, stock, precio) VALUES
('Laptop Dell Inspiron 15', 1, 15, 720.00),
('Mouse Inalámbrico Logitech', 2, 25, 12.00);

-- REPORTES RELACIONALES AVANZADOS (Guía 11)
-- 1. Vista completa del inventario con categorías legibles para administración:
SELECT p.id, p.nombre_producto, c.nombre_categoria, p.stock, p.precio 
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id;

-- 2. Vista filtrada exclusivamente para el departamento de 'Accesorios':
SELECT p.id, p.nombre_producto, c.nombre_categoria, p.stock, p.precio 
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id 
WHERE c.nombre_categoria = 'Accesorios';

-- ====================================================================
-- CONSULTAS DE ESTADÍSTICAS Y MÉTRICAS PARA EL DASHBOARD (Guía 12)
-- ====================================================================

-- Tarjeta 1: Total de artículos distintos en el catálogo (Muestra variedad de productos)
SELECT COUNT(id) AS total_productos_catalogo FROM productos;

-- Tarjeta 2: Valor económico total del inventario (Multiplica precio por existencias de cada fila y las suma)
SELECT SUM(precio * stock) AS valor_monetario_inventario FROM productos;

-- Tarjeta 3: Precio del producto estrella o de mayor gama del inventario
SELECT MAX(precio) AS producto_mas_caro FROM productos;

-- Tarjeta 4: Reporte de unidades físicas totales en existencia agrupadas por categoría
SELECT c.nombre_categoria, SUM(p.stock) AS existencias_totales
FROM productos p
INNER JOIN categorias c ON p.categoria_id = c.id
GROUP BY c.nombre_categoria;
 
 CREATE TABLE IF NOT EXISTS clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_completo VARCHAR(150) NOT NULL,
    tipo_documento ENUM('DUI', 'NIT', 'PASAPORTE') NOT NULL DEFAULT 'DUI',
    numero_documento VARCHAR(20) NOT NULL UNIQUE,
    telefono VARCHAR(15) NULL,
    email VARCHAR(100) NULL,
    direccion TEXT NULL,
    tipo_cliente ENUM('FINAL', 'FISCAL') NOT NULL DEFAULT 'FINAL',
    nrc VARCHAR(20) NULL,
    limite_credito DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    saldo_actual DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado ENUM('ACTIVO', 'BLOQUEADO', 'INACTIVO') NOT NULL DEFAULT 'ACTIVO',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_factura VARCHAR(25) NOT NULL UNIQUE,
    tipo_comprobante ENUM('TICKET', 'FACTURA', 'CREDITO_FISCAL') NOT NULL,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    condicion_pago ENUM('CONTADO', 'CREDITO') NOT NULL DEFAULT 'CONTADO',
    dias_credito INT NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    iva DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total DECIMAL(10,2) NOT NULL,
    estado ENUM('COMPLETADA', 'ANULADA', 'PENDIENTE_PAGO') NOT NULL DEFAULT 'COMPLETADA',
    fecha_venta TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_ventas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO clientes (nombre_completo, tipo_documento, numero_documento, telefono, tipo_cliente, limite_credito, saldo_actual, estado) VALUES
('Consumidor Final (Venta Rápida)', 'DUI', '00000000-0', '2660-0000', 'FINAL', 0.00, 0.00, 'ACTIVO'),
('Distribuidora El Progreso S.A. de C.V.', 'NIT', '0614-120598-102-1', '2665-1234', 'FISCAL', 2500.00, 650.00, 'ACTIVO'),
('Lic. Carlos Alberto Mendoza', 'DUI', '02345678-9', '7890-4321', 'FINAL', 500.00, 480.00, 'ACTIVO'),
('Comercial Santa Anita', 'NIT', '0614-010190-101-5', '2665-9876', 'FISCAL', 1000.00, 1000.00, 'BLOQUEADO');
