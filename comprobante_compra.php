<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Identificador de compra no especificado.");
}
$compra_id = intval($_GET['id']);

// Consulta de Cabecera
$sql_cab = "SELECT c.id, c.fecha, c.total, c.estado, 
                   p.nombre_empresa, p.contacto, p.telefono, p.direccion, 
                   u.nombre_completo AS usuario_nombre 
            FROM compras c 
            INNER JOIN proveedores p ON c.proveedor_id = p.id 
            INNER JOIN usuarios u ON c.usuario_id = u.id 
            WHERE c.id = ? LIMIT 1";
$stmt_cab = $conn->prepare($sql_cab);
$stmt_cab->bind_param("i", $compra_id);
$stmt_cab->execute();
$compra = $stmt_cab->get_result()->fetch_assoc();

if (!$compra) {
    die("Error: La factura de compra no existe en el sistema.");
}

// Consulta de Líneas de Detalle
$sql_det = "SELECT d.cantidad, d.precio_compra AS precio_unitario, 
                   (d.cantidad * d.precio_compra) AS subtotal, 
                   pr.nombre_producto AS producto 
            FROM detalle_compras d 
            INNER JOIN productos pr ON d.producto_id = pr.id 
            WHERE d.compra_id = ?";
$stmt_det = $conn->prepare($sql_det);
$stmt_det->bind_param("i", $compra_id);
$stmt_det->execute();
$res_det = $stmt_det->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante de Compra #<?php echo str_pad($compra['id'], 6, '0', STR_PAD_LEFT); ?></title>
    <link rel="stylesheet" href="https://jsdelivr.net">
    <style>
        body { background-color: #F4F6F9; color: #1E293B; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .hoja-impresion { background-color: #FFFFFF; max-width: 850px; margin: 30px auto; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .marca-anulada { color: #DC2626; border: 3px solid #DC2626; padding: 5px 15px; font-weight: bold; text-transform: uppercase; display: inline-block; transform: rotate(-5deg); margin-top: 10px; }
        .linea-firma { border-top: 1px solid #334155; width: 80%; margin: 50px auto 5px auto; }
        @media print {
            body { background: #FFFFFF !important; padding: 0 !important; }
            .no-print { display: none !important; }
            .hoja-impresion { box-shadow: none !important; margin: 0 !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
            @page { size: letter; margin: 1.5cm; }
        }
    </style>
</head>
<body>
    <div class="container text-center mt-3 no-print">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold shadow-sm">🖨️ Imprimir / Guardar PDF</button>
        <a href="historial_compras.php" class="btn btn-secondary font-weight-bold shadow-sm ml-2">⬅️ Volver al Historial</a>
    </div>

    <div class="hoja-impresion">
        <div class="row border-bottom pb-3 mb-4">
            <div class="col-8">
                <h3 class="font-weight-bold mb-1 text-primary">SISTEMA DE INVENTARIO Y VENTAS</h3>
                <p class="mb-0 text-muted">Instituto Nacional de Ciudad Barrios - BTV en Desarrollo de Software</p>
                <p class="mb-0 small text-muted">Departamento de Compras y Control de Inventarios | San Miguel, El Salvador</p>
            </div>
            <div class="col-4 text-right">
                <h5 class="font-weight-bold mb-0">COMPROBANTE DE COMPRA</h5>
                <h4 class="text-danger font-weight-bold mb-1">#<?php echo str_pad($compra['id'], 6, '0', STR_PAD_LEFT); ?></h4>
                <small class="text-muted">Fecha: <?php echo date('d/m/Y H:i', strtotime($compra['fecha'])); ?></small>
                <?php if ($compra['estado'] === 'ANULADA'): ?>
                    <div><span class="marca-anulada">FACTURA ANULADA</span></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-6">
                <h6 class="font-weight-bold text-uppercase text-secondary">Datos del Proveedor:</h6>
                <p class="mb-1"><strong>Empresa:</strong> <?php echo htmlspecialchars($compra['nombre_empresa']); ?></p>
                <p class="mb-1"><strong>Contacto:</strong> <?php echo htmlspecialchars($compra['contacto']); ?></p>
                <p class="mb-1"><strong>Teléfono:</strong> <?php echo htmlspecialchars($compra['telefono']); ?></p>
                <p class="mb-0"><strong>Dirección:</strong> <?php echo htmlspecialchars($compra['direccion']); ?></p>
            </div>
            <div class="col-6 text-right">
                <h6 class="font-weight-bold text-uppercase text-secondary">Datos del Registro:</h6>
                <p class="mb-1"><strong>Recibido por:</strong> <?php echo htmlspecialchars($compra['usuario_nombre']); ?></p>
                <p class="mb-1"><strong>Estado:</strong> <span class="badge <?php echo ($compra['estado'] === 'COMPLETADA') ? 'badge-success' : 'badge-danger'; ?>"><?php echo $compra['estado']; ?></span></p>
                <p class="mb-0"><strong>Moneda:</strong> Dólares Americanos (USD)</p>
            </div>
        </div>

        <table class="table table-bordered mb-4">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Descripción del Artículo</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-right">Precio Unitario</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php $i = 1; while ($d = $res_det->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo htmlspecialchars($d['producto']); ?></td>
                    <td class="text-center font-weight-bold"><?php echo $d['cantidad']; ?></td>
                    <td class="text-right">$<?php echo number_format($d['precio_unitario'], 2); ?></td>
                    <td class="text-right font-weight-bold">$<?php echo number_format($d['subtotal'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="4" class="text-right font-weight-bold">TOTAL GENERAL DE COMPRA:</th>
                    <th class="text-right text-primary font-weight-bold h5 mb-0">$<?php echo number_format($compra['total'], 2); ?></th>
                </tr>
            </tfoot>
        </table>

        <div class="row firmas-seccion text-center pt-4">
            <div class="col-6">
                <div class="linea-firma"></div>
                <p class="mb-0 font-weight-bold">Recibido Conforme (Bodega)</p>
                <small class="text-muted">Firma y Sello de Almacén</small>
            </div>
            <div class="col-6">
                <div class="linea-firma"></div>
                <p class="mb-0 font-weight-bold">Autorizado (Compras)</p>
                <small class="text-muted">Gerencia Administrativa / Finanzas</small>
            </div>
        </div>
    </div>
</body>
</html>
