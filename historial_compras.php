<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

$fecha_inicio = isset($_GET['fecha_inicio']) ? trim($_GET['fecha_inicio']) : '';
$fecha_fin    = isset($_GET['fecha_fin']) ? trim($_GET['fecha_fin']) : '';
$id_proveedor = isset($_GET['id_proveedor']) ? intval($_GET['id_proveedor']) : 0;
$estado       = isset($_GET['estado']) ? trim($_GET['estado']) : '';

$res_prov = $conn->query("SELECT id, nombre_empresa FROM proveedores ORDER BY nombre_empresa ASC");

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
    <style>
        body { font-family: 'Segoe UI', Tahoma, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; color: #1e293b; }
        .contenedor { max-width: 1100px; margin: 0 auto; }
        .header { display: flex; justify-content: space-between; align-items: center; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .header h2 { margin: 0; }
        .btn { padding: 8px 14px; border-radius: 5px; text-decoration: none; font-weight: bold; font-size: 14px; display: inline-block; border: none; cursor: pointer; color: white; }
        .btn-info { background: #0284c7; }
        .btn-success { background: #10b981; }
        .btn-secondary { background: #64748b; }
        .btn-primary { background: #2563eb; }
        .btn-sm { padding: 5px 10px; font-size: 12px; }
        .card-filtros { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; overflow: hidden; }
        .card-filtros-header { background: #1e293b; color: white; padding: 12px 20px; font-weight: bold; }
        .filtros-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; padding: 20px; }
        .filtros-grid label { display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #475569; }
        .filtros-grid input, .filtros-grid select { width: 100%; padding: 8px; border: 1px solid #cbd5e1; border-radius: 4px; box-sizing: border-box; }
        .filtros-acciones { padding: 0 20px 20px 20px; text-align: right; }
        .kpis { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 20px; }
        .kpi { padding: 20px; border-radius: 8px; color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .kpi h4 { margin: 0 0 8px 0; font-size: 13px; text-transform: uppercase; opacity: 0.9; }
        .kpi .valor { font-size: 28px; font-weight: bold; margin: 0 0 5px 0; }
        .kpi-verde { background: #10b981; }
        .kpi-azul { background: #2563eb; }
        .kpi-rojo { background: #dc2626; }
        .tabla-card { background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); overflow: hidden; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        th { background: #1e293b; color: white; font-size: 14px; }
        tr:hover { background: #f8fafc; }
        .badge { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .badge-activa { background: #dcfce7; color: #15803d; }
        .badge-anulada { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="header">
        <h2>📜 Historial de Compras</h2>
        <div>
            <a href="reporte_compras.php" class="btn btn-info">📊 Reporte Gerencial</a>
            <a href="nueva_compra.php" class="btn btn-success">+ Nueva Compra</a>
            <a href="dashboard.php" class="btn btn-secondary">Volver al Panel</a>
        </div>
    </div>

    <div class="card-filtros">
        <div class="card-filtros-header">🔍 Filtros de Búsqueda Avanzada</div>
        <form method="GET" action="historial_compras.php">
            <div class="filtros-grid">
                <div>
                    <label>Desde Fecha:</label>
                    <input type="date" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div>
                    <label>Hasta Fecha:</label>
                    <input type="date" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                <div>
                    <label>Proveedor:</label>
                    <select name="id_proveedor">
                        <option value="0">-- Todos los Proveedores --</option>
                        <?php while ($p = $res_prov->fetch_assoc()) { ?>
                            <option value="<?php echo $p['id']; ?>" <?php echo ($id_proveedor == $p['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($p['nombre_empresa']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label>Estado de Factura:</label>
                    <select name="estado">
                        <option value="">-- Todos --</option>
                        <option value="COMPLETADA" <?php echo ($estado === 'COMPLETADA') ? 'selected' : ''; ?>>Completada</option>
                        <option value="ANULADA" <?php echo ($estado === 'ANULADA') ? 'selected' : ''; ?>>Anulada</option>
                    </select>
                </div>
            </div>
            <div class="filtros-acciones">
                <button type="submit" class="btn btn-primary">⚡ Aplicar Filtros</button>
                <a href="historial_compras.php" class="btn btn-secondary">🔄 Limpiar Filtros</a>
            </div>
        </form>
    </div>

    <div class="kpis">
        <div class="kpi kpi-verde">
            <h4>Monto Total Invertido</h4>
            <p class="valor">$<?php echo number_format($total_gastado, 2); ?></p>
            <small>Capital neto en compras válidas</small>
        </div>
        <div class="kpi kpi-azul">
            <h4>Compras Completadas</h4>
            <p class="valor"><?php echo $conteo_completadas; ?></p>
            <small>Facturas vigentes en inventario</small>
        </div>
        <div class="kpi kpi-rojo">
            <h4>Facturas Anuladas</h4>
            <p class="valor"><?php echo $conteo_anuladas; ?></p>
            <small>Operaciones revertidas sin costo</small>
        </div>
    </div>

    <div class="tabla-card">
        <table>
            <thead>
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
                <?php if (count($filas_compras) > 0) { ?>
                    <?php foreach ($filas_compras as $fila) { ?>
                    <tr>
                        <td>#<?php echo $fila['numero_factura']; ?></td>
                        <td><?php echo $fila['fecha']; ?></td>
                        <td><?php echo htmlspecialchars($fila['proveedor']); ?></td>
                        <td><?php echo htmlspecialchars($fila['usuario']); ?></td>
                        <td style="color:#059669; font-weight:bold;">$<?php echo number_format($fila['total'], 2); ?></td>
                        <td>
                            <?php if ($fila['estado'] === 'COMPLETADA') { ?>
                                <span class="badge badge-activa">ACTIVA</span>
                            <?php } else { ?>
                                <span class="badge badge-anulada">ANULADA</span>
                            <?php } ?>
                        </td>
                        <td>
                            <a href="detalle_compra.php?id=<?php echo $fila['numero_factura']; ?>" class="btn btn-primary btn-sm">Ver Detalle</a>
                            <a href="comprobante_compra.php?id=<?php echo $fila['numero_factura']; ?>" target="_blank" class="btn btn-secondary btn-sm">🖨️</a>
                        </td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="7" style="text-align:center; padding:25px;">Sin registros de compras disponibles.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
