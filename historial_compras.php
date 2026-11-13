<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
require_once 'conexion.php';

$sql = "SELECT c.id AS numero_factura, c.fecha, c.total, c.estado, 
               p.nombre_empresa AS proveedor, u.nombre_completo AS usuario 
        FROM compras c 
        INNER JOIN proveedores p ON c.proveedor_id = p.id 
        INNER JOIN usuarios u ON c.usuario_id = u.id 
        ORDER BY c.fecha DESC";
$resultado = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de Compras</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f6f9; margin: 20px; }
        .contenedor { max-width: 1050px; margin: 0 auto; background: #ffffff; padding: 25px; border-radius: 6px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .encabezado { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .btn-regresar { background: #64748b; color: #ffffff; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; }
        .btn-nueva { background: #10b981; color: #ffffff; padding: 8px 12px; text-decoration: none; border-radius: 4px; font-weight: bold; margin-right: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        th { background-color: #1e293b; color: #ffffff; }
        .monto { color: #059669; font-weight: bold; }
    </style>
</head>
<body>
<div class="contenedor">
    <div class="encabezado">
        <h2>Historial de Ingresos de Mercadería</h2>
        <div>
            <a href="nueva_compra.php" class="btn-nueva">+ Nueva Compra</a>
            <a href="dashboard.php" class="btn-regresar">Regresar al Panel</a>
        </div>
    </div>
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
            <?php if ($resultado->num_rows > 0): ?>
                <?php while($fila = $resultado->fetch_assoc()): ?>
                <tr>
                    <td>#<?php echo $fila['numero_factura']; ?></td>
                    <td><?php echo $fila['fecha']; ?></td>
                    <td><?php echo htmlspecialchars($fila['proveedor']); ?></td>
                    <td><?php echo htmlspecialchars($fila['usuario']); ?></td>
                    <td class="monto">$<?php echo number_format($fila['total'], 2); ?></td>
                    <td>
                        <?php if ($fila['estado'] === 'COMPLETADA'): ?>
                            <span style="background: #dcfce7; color: #15803d; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">ACTIVA</span>
                        <?php else: ?>
                            <span style="background: #fee2e2; color: #b91c1c; padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold;">ANULADA</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="detalle_compra.php?id=<?php echo $fila['numero_factura']; ?>" style="background: #2563eb; color: white; padding: 6px 12px; text-decoration: none; border-radius: 4px; font-size: 13px; font-weight: bold;">Ver Detalle</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="7" style="text-align:center;">Sin registros de compras disponibles.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
