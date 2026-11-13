<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

$sql_prov = "SELECT p.nombre_empresa, 
                    COUNT(c.id) AS total_facturas, 
                    SUM(c.total) AS total_invertido 
             FROM compras c 
             INNER JOIN proveedores p ON c.proveedor_id = p.id 
             WHERE c.estado = 'COMPLETADA' 
             GROUP BY p.id, p.nombre_empresa 
             ORDER BY total_invertido DESC";
$res_prov = $conn->query($sql_prov);

$nombres_proveedores = [];
$montos_proveedores = [];
$filas_tabla = [];
$gran_total_compras = 0.00;

while ($fila = $res_prov->fetch_assoc()) {
    $filas_tabla[] = $fila;
    $nombres_proveedores[] = $fila['nombre_empresa'];
    $montos_proveedores[] = (float)$fila['total_invertido'];
    $gran_total_compras += (float)$fila['total_invertido'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Gerencial de Compras - INCB</title>
    <link rel="stylesheet" href="https://jsdelivr.net">
    <script src="https://jsdelivr.net"></script>
    <style>
        body { background-color: #F8FAFC; color: #1E293B; font-family: 'Segoe UI', Tahoma, sans-serif; }
        .card-dash { background: #FFFFFF; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); padding: 25px; margin-bottom: 25px; }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark bg-dark mb-4 shadow-sm">
        <div class="container">
            <span class="navbar-brand font-weight-bold">📊 Sistema de Inventario y Ventas - INCB</span>
            <a href="historial_compras.php" class="btn btn-outline-light btn-sm">⬅️ Volver al Historial</a>
        </div>
    </nav>

    <div class="container mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="font-weight-bold text-dark mb-0">Cierre Gerencial: Módulo de Compras</h3>
                <p class="text-muted mb-0">Consolidado de compras válidas por proveedor e indicadores estratégicos.</p>
            </div>
            <div>
                <a href="exportar_compras_csv.php" class="btn btn-success font-weight-bold shadow-sm">📥 Descargar Reporte en Excel (CSV)</a>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-4">
                <div class="card-dash text-center bg-primary text-white">
                    <h6 class="text-uppercase font-weight-bold mb-2">Presupuesto Total Invertido</h6>
                    <h2 class="display-4 font-weight-bold mb-0">$<?php echo number_format($gran_total_compras, 2); ?></h2>
                    <small>Monto neto en compras activas</small>
                </div>
                <div class="card-dash">
                    <h6 class="font-weight-bold text-secondary mb-3">Indicadores Clave (KPIs)</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Proveedores Atendidos: <span class="badge badge-primary badge-pill"><?php echo count($filas_tabla); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            Estado del Módulo: <span class="badge badge-success">Auditoría Concluida</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card-dash">
                    <h6 class="font-weight-bold text-dark mb-3">Distribución de Gasto por Proveedor (Gráfica Interactiva)</h6>
                    <canvas id="graficaCompras" height="140"></canvas>
                </div>
            </div>
        </div>

        <div class="card-dash p-0 overflow-hidden">
            <div class="card-header bg-dark text-white font-weight-bold">Detalle de Cierre por Proveedor</div>
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>#</th>
                            <th>Nombre del Proveedor</th>
                            <th class="text-center">Facturas Emitidas</th>
                            <th class="text-right">Monto Invertido ($)</th>
                            <th class="text-right">% de Participación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; foreach ($filas_tabla as $prov): 
                            $porcentaje = ($gran_total_compras > 0) ? ($prov['total_invertido'] / $gran_total_compras) * 100 : 0;
                        ?>
                        <tr>
                            <td><?php echo $i++; ?></td>
                            <td class="font-weight-bold"><?php echo htmlspecialchars($prov['nombre_empresa']); ?></td>
                            <td class="text-center"><?php echo $prov['total_facturas']; ?></td>
                            <td class="text-right font-weight-bold">$<?php echo number_format($prov['total_invertido'], 2); ?></td>
                            <td class="text-right"><?php echo number_format($porcentaje, 1); ?>%</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('graficaCompras').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($nombres_proveedores); ?>,
                datasets: [{
                    label: 'Total Invertido ($ USD)',
                    data: <?php echo json_encode($montos_proveedores); ?>,
                    backgroundColor: 'rgba(2, 132, 199, 0.7)',
                    borderColor: 'rgba(2, 132, 199, 1)',
                    borderWidth: 1,
                    borderRadius: 6
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } }
            }
        });
    </script>
</body>
</html>
