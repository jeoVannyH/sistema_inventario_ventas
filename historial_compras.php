<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

// 1. Recepción y sanitización de filtros GET
$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin    = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$id_proveedor = isset($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : 0;
$estado       = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// 2. Consulta de proveedores para el <select>
$res_prov = $conn->query("SELECT id, nombre_empresa FROM proveedores ORDER BY nombre_empresa ASC");

// 3. Consulta dinámica con WHERE 1=1
$sql = "SELECT c.id AS numero_factura, c.fecha, c.total, c.estado, 
               p.nombre_empresa AS proveedor, u.nombre_completo AS usuario 
        FROM compras c 
        INNER JOIN proveedores p ON c.proveedor_id = p.id 
        INNER JOIN usuarios u ON c.usuario_id = u.id 
        WHERE 1=1";

$tipos = "";
$parametros = [];

if (!empty($fecha_inicio) && !empty($fecha_fin)) {
    $sql .= " AND DATE(c.fecha) BETWEEN ? AND ?";
    $tipos .= "ss";
    $parametros[] = $fecha_inicio;
    $parametros[] = $fecha_fin;
}
if ($id_proveedor > 0) {
    $sql .= " AND c.proveedor_id = ?";
    $tipos .= "i";
    $parametros[] = $id_proveedor;
}
if (!empty($estado) && in_array($estado, ['COMPLETADA', 'ANULADA'])) {
    $sql .= " AND c.estado = ?";
    $tipos .= "s";
    $parametros[] = $estado;
}

$sql .= " ORDER BY c.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($tipos)) {
    $stmt->bind_param($tipos, ...$parametros);
}
$stmt->execute();
$resultado = $stmt->get_result();

// 4. Cálculo acumulado de métricas
$total_gastado = 0.00;
$conteo_completadas = 0;
$conteo_anuladas = 0;
$filas_compras = [];

while ($fila = $resultado->fetch_assoc()) {
    $filas_compras[] = $fila;
    if ($fila['estado'] === 'COMPLETADA') {
        $total_gastado += (float)$fila['total'];
        $conteo_completadas++;
    } else {
        $conteo_anuladas++;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Compras</title>
    <link rel="stylesheet" href="https://jsdelivr.net">
</head>
<body class="bg-light p-4">
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="font-weight-bold text-dark mb-0">📜 Historial de Compras</h2>
        <div>
            <a href="reporte_compras.php" class="btn btn-info font-weight-bold shadow-sm">📊 Reporte Gerencial</a>
            <a href="nueva_compra.php" class="btn btn-success font-weight-bold shadow-sm">+ Nueva Compra</a>
            <a href="dashboard.php" class="btn btn-secondary font-weight-bold shadow-sm">Volver al Panel</a>
        </div>
    </div>

    <!-- Formulario de Filtros Multicriterio -->
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-dark text-white font-weight-bold">🔍 Filtros de Búsqueda Avanzada</div>
        <div class="card-body">
            <form method="GET" action="historial_compras.php" class="row">
                <div class="col-md-3 mb-2">
                    <label>Desde Fecha:</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Hasta Fecha:</label>
                    <input type="date" name="fecha_fin" class="form-control" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label>Proveedor:</label>
                    <select name="id_proveedor" class="form-control">
                        <option value="0">-- Todos los Proveedores --</option>
                        <?php while ($p = $res_prov->fetch_assoc()): ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($id_proveedor == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre_empresa']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3 mb-2">
                    <label>Estado de Factura:</label>
                    <select name="estado" class="form-control">
                        <option value="">-- Todos --</option>
                        <option value="COMPLETADA" <?php echo ($estado === 'COMPLETADA') ? 'selected' : ''; ?>>Completada</option>
                        <option value="ANULADA" <?php echo ($estado === 'ANULADA') ? 'selected' : ''; ?>>Anulada</option>
                    </select>
                </div>
                <div class="col-md-12 text-right mt-2">
                    <button type="submit" class="btn btn-primary font-weight-bold">⚡ Aplicar Filtros</button>
                    <a href="historial_compras.php" class="btn btn-secondary font-weight-bold">🔄 Limpiar Filtros</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Panel de Métricas Financieras -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-uppercase mb-1">Monto Total Invertido</h6>
                    <h2 class="font-weight-bold mb-0">$<?php echo number_format($total_gastado, 2); ?></h2>
                    <small>Capital neto en compras válidas</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-primary text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-uppercase mb-1">Compras Completadas</h6>
                    <h2 class="font-weight-bold mb-0"><?php echo $conteo_completadas; ?></h2>
                    <small>Facturas vigentes en inventario</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white shadow-sm">
                <div class="card-body">
                    <h6 class="card-title text-uppercase mb-1">Facturas Anuladas</h6>
                    <h2 class="font-weight-bold mb-0"><?php echo $conteo_anuladas; ?></h2>
                    <small>Operaciones revertidas sin costo</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Resultados -->
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>Número</th>
                        <th>Fecha y Hora</th>
                        <th>Proveedor</th>
                        <th>Usuario Responsable</th>
                        <th>Total Invertido</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($filas_compras) > 0): ?>
                        <?php foreach ($filas_compras as $fila): ?>
                        <tr>
                            <td>#<?php echo $fila['numero_factura']; ?></td>
                            <td><?php echo $fila['fecha']; ?></td>
                            <td><?php echo htmlspecialchars($fila['proveedor']); ?></td>
                            <td><?php echo htmlspecialchars($fila['usuario']); ?></td>
                            <td class="font-weight-bold text-success">$<?php echo number_format($fila['total'], 2); ?></td>
                            <td>
                                <?php if ($fila['estado'] === 'COMPLETADA'): ?>
                                    <span class="badge badge-success">ACTIVA</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">ANULADA</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="detalle_compra.php?id=<?php echo $fila['numero_factura']; ?>" class="btn btn-sm btn-primary font-weight-bold">Ver Detalle</a>
                                <a href="comprobante_compra.php?id=<?php echo $fila['numero_factura']; ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="Imprimir Comprobante">🖨️</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4">Sin registros de compras disponibles.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
