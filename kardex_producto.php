<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: Debe seleccionar un producto para consultar su Kardex.");
}
$producto_id = intval($_GET['id']);

// Consulta de información del Producto (adaptada a tus columnas reales)
$sql_prod = "SELECT id, nombre_producto AS nombre, precio, stock FROM productos WHERE id = ? LIMIT 1";
$stmt_prod = $conn->prepare($sql_prod);
$stmt_prod->bind_param("i", $producto_id);
$stmt_prod->execute();
$prod = $stmt_prod->get_result()->fetch_assoc();

if (!$prod) {
    die("Error: El producto especificado no existe.");
}

// Consulta de movimientos del Kardex
$sql_k = "SELECT k.*, c.fecha AS fecha_compra 
          FROM kardex k 
          LEFT JOIN compras c ON (k.id_documento = c.id AND k.tipo_movimiento = 'ENTRADA_COMPRA') 
          WHERE k.id_producto = ? 
          ORDER BY k.id DESC";
$stmt_k = $conn->prepare($sql_k);
$stmt_k->bind_param("i", $producto_id);
$stmt_k->execute();
$res_k = $stmt_k->get_result();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Kardex de Inventario - <?php echo htmlspecialchars($prod['nombre']); ?></title>
    <link rel="stylesheet" href="https://jsdelivr.net">
</head>
<body class="bg-light">
    <div class="container my-4">
        <div class="card shadow-sm mb-4">
            <div class="card-body d-flex justify-content-between align-items-center">
                <div>
                    <h3 class="font-weight-bold text-primary mb-1">📋 Kardex de Movimientos Físicos</h3>
                    <h5 class="mb-0 text-dark">Producto: <strong><?php echo htmlspecialchars($prod['nombre']); ?></strong> (ID: #<?php echo $prod['id']; ?>)</h5>
                </div>
                <div class="text-right">
                    <span class="badge badge-success p-2 h6 mb-0">Stock Actual: <?php echo $prod['stock']; ?> unidades</span>
                    <div class="mt-2">
                        <a href="inventario.php" class="btn btn-sm btn-outline-secondary">⬅️ Volver al Inventario</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white font-weight-bold">Historial Cronológico de Entradas y Salidas</div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped mb-0 text-center">
                    <thead class="thead-light">
                        <tr>
                            <th># Trans.</th>
                            <th>Fecha / Hora</th>
                            <th>Tipo Movimiento</th>
                            <th>Doc. Ref.</th>
                            <th>Costo Unit.</th>
                            <th>Stock Anterior</th>
                            <th class="text-success">+ Entrada</th>
                            <th class="text-danger">- Salida</th>
                            <th class="font-weight-bold">Nuevo Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_k->num_rows == 0): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">No hay movimientos registrados para este artículo.</td></tr>
                        <?php else: ?>
                            <?php while ($mov = $res_k->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $mov['id']; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($mov['fecha'])); ?></td>
                                <td>
                                    <?php if ($mov['tipo_movimiento'] === 'ENTRADA_COMPRA'): ?>
                                        <span class="badge badge-primary">📥 Compra</span>
                                    <?php elseif ($mov['tipo_movimiento'] === 'ANULACION_COMPRA'): ?>
                                        <span class="badge badge-danger">↩️ Reversión Anulación</span>
                                    <?php else: ?>
                                        <span class="badge badge-info"><?php echo $mov['tipo_movimiento']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><a href="detalle_compra.php?id=<?php echo $mov['id_documento']; ?>" target="_blank">Factura #<?php echo $mov['id_documento']; ?></a></td>
                                <td>$<?php echo number_format($mov['costo_unitario'], 2); ?></td>
                                <td class="text-muted"><?php echo $mov['stock_anterior']; ?></td>
                                <td class="font-weight-bold text-success"><?php echo ($mov['cantidad_entrada'] > 0) ? '+' . $mov['cantidad_entrada'] : '-'; ?></td>
                                <td class="font-weight-bold text-danger"><?php echo ($mov['cantidad_salida'] > 0) ? '-' . $mov['cantidad_salida'] : '-'; ?></td>
                                <td class="font-weight-bold h6 mb-0 text-dark"><?php echo $mov['stock_actual']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
